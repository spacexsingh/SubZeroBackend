<?php

namespace App\Console\Commands;

use App\Enums\UserType;
use App\Models\LumaEvent;
use App\Models\LumaGuest;
use App\Models\LumaGuestWalletAddress;
use App\Models\PointBalanceSnapshot;
use App\Models\User;
use App\Services\LumaService;
use App\Traits\ManagesLumaGuestUsers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImportExcelBackup extends Command
{
    use ManagesLumaGuestUsers;

    /**
     * The Luma service instance.
     */
    protected LumaService $lumaService;

    /**
     * API key cache per event.
     */
    protected ?string $apiKey = null;

    /**
     * Create a new command instance.
     */
    public function __construct(LumaService $lumaService)
    {
        parent::__construct();
        $this->lumaService = $lumaService;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:import-excel-backup
                            {file : Path to the Excel file}
                            {--event-id= : Luma event ID to associate with}
                            {--dry-run : Preview what will be imported without saving}
                            {--skip-luma-api : Skip fetching from Luma API, use Excel data only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import user data and point balances from Excel backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('📊 Starting Excel backup import...');

        if ($this->option('dry-run')) {
            $this->warn('🔍 DRY RUN MODE - No data will be saved');
        }

        try {
            // Check if pandas is available
            $this->checkPythonDependencies();

            DB::beginTransaction();

            $stats = [
                'users_created' => 0,
                'users_updated' => 0,
                'guests_created' => 0,
                'guests_synced_from_luma' => 0,
                'wallets_created' => 0,
                'snapshots_created' => 0,
            ];

            // Get Luma event if specified
            $lumaEvent = null;
            if ($eventId = $this->option('event-id')) {
                $lumaEvent = LumaEvent::where('luma_event_id', $eventId)
                    ->with(['sideEvent.conference.lumaApiKey'])
                    ->first();

                if (!$lumaEvent) {
                    $this->warn("Warning: Luma event {$eventId} not found. Continuing without event association.");
                } else {
                    // Get API key
                    $this->apiKey = $lumaEvent->sideEvent->conference->lumaApiKey->api_key ?? null;

                    if (!$this->option('skip-luma-api') && $this->apiKey) {
                        $this->line("\n🔄 Syncing guests from Luma API first...");
                        $syncedCount = $this->syncGuestsFromLuma($lumaEvent);
                        $stats['guests_synced_from_luma'] = $syncedCount;
                        $this->info("✓ Synced {$syncedCount} guests from Luma API\n");
                    }
                }
            }

            // Import from each sheet
            $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info('📥 Processing: Users with Points');
            $this->importUsersWithPoints($filePath, $lumaEvent, $stats);

            $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info('📥 Processing: Users with No Points');
            $this->importUsersWithNoPoints($filePath, $lumaEvent, $stats);

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->warn('🔍 DRY RUN COMPLETED - No changes were saved');
            } else {
                DB::commit();
                $this->line("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
                $this->info('✅ Import completed successfully!');
            }

            // Display statistics
            $this->displayStats($stats);

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Check if required Python dependencies are installed.
     */
    protected function checkPythonDependencies(): void
    {
        exec('python3 -c "import pandas, openpyxl" 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $this->warn('Installing required Python packages (pandas, openpyxl)...');
            exec('pip3 install --break-system-packages pandas openpyxl 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Failed to install required Python packages. Please install pandas and openpyxl manually.');
            }
        }
    }

    /**
     * Import users with point balances.
     */
    protected function importUsersWithPoints(string $filePath, ?LumaEvent $lumaEvent, array &$stats): void
    {
        $data = $this->readExcelSheet($filePath, 'Users_With_Points');

        $this->line("   Found {$data['count']} users with points");

        $progressBar = $this->output->createProgressBar($data['count']);
        $progressBar->start();

        foreach ($data['rows'] as $row) {
            $this->processUserRow($row, $lumaEvent, $stats, true);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Import users without point balances.
     */
    protected function importUsersWithNoPoints(string $filePath, ?LumaEvent $lumaEvent, array &$stats): void
    {
        $data = $this->readExcelSheet($filePath, 'Users_With_No_Points');

        $this->line("   Found {$data['count']} users without points");

        $progressBar = $this->output->createProgressBar($data['count']);
        $progressBar->start();

        foreach ($data['rows'] as $row) {
            $this->processUserRow($row, $lumaEvent, $stats, false);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
    }

    /**
     * Process a single user row.
     */
    protected function processUserRow(array $row, ?LumaEvent $lumaEvent, array &$stats, bool $hasPoints): void
    {
        $email = $row['Email'] ?? null;
        $name = $row['Name'] ?? 'Unknown';
        $lumaId = $row['Luma ID'] ?? null;
        $walletAddress = $row['Nova Account Address'] ?? null;

        if (!$email) {
            return;
        }

        if ($this->option('dry-run')) {
            return;
        }

        // Create or update user
        $user = User::where('email', $email)->first();

        if ($user) {
            $stats['users_updated']++;
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'user_type' => UserType::GENERAL_ATTENDEE,
                'password' => Hash::make(Str::random(32)),
            ]);
            $stats['users_created']++;
        }

        // Find or link Luma guest if we have a Luma ID
        if ($lumaId && $lumaEvent) {
            $guest = LumaGuest::where('guest_id', $lumaId)->first();

            // If guest exists from Luma API sync, just link the user
            if ($guest) {
                // Update user_id if not already set
                if (!$guest->user_id) {
                    $guest->update(['user_id' => $user->id]);
                }

                // Create wallet address if we have one
                if ($walletAddress && !empty($walletAddress)) {
                    $existing = LumaGuestWalletAddress::where('luma_guest_id', $guest->id)
                        ->where('wallet_address', $walletAddress)
                        ->exists();

                    if (!$existing) {
                        LumaGuestWalletAddress::create([
                            'luma_guest_id' => $guest->id,
                            'wallet_address' => $walletAddress,
                            'chain' => 'polkadot',
                            'is_verified' => false,
                        ]);
                        $stats['wallets_created']++;
                    }
                }
            }
        }

        // Create point balance snapshots if we have point data
        if ($hasPoints) {
            $this->createPointSnapshots($user, $lumaEvent, $row, $stats);
        }
    }

    /**
     * Create point balance snapshots for a user.
     */
    protected function createPointSnapshots(User $user, ?LumaEvent $lumaEvent, array $row, array &$stats): void
    {
        // Conference dates (Nov 14-16, 2025 for Sub0)
        $dates = [
            '14' => Carbon::create(2025, 11, 14),
            '15' => Carbon::create(2025, 11, 15),
            '16' => Carbon::create(2025, 11, 16),
        ];

        $cumulativeEarned = 0;
        $cumulativeRedeemed = 0;

        foreach ($dates as $day => $date) {
            $earnedKey = "Earned_{$day}Nov";
            $redeemedKey = "Redeemed_{$day}Nov";

            $earned = (int) ($row[$earnedKey] ?? 0);
            $redeemed = abs((int) ($row[$redeemedKey] ?? 0));

            // Only create snapshot if there was activity on this day
            if ($earned > 0 || $redeemed > 0) {
                $cumulativeEarned += $earned;
                $cumulativeRedeemed += $redeemed;
                $balance = $cumulativeEarned - $cumulativeRedeemed;

                $existing = PointBalanceSnapshot::where('user_id', $user->id)
                    ->where('snapshot_date', $date)
                    ->where('luma_event_id', $lumaEvent?->id)
                    ->exists();

                if (!$existing) {
                    PointBalanceSnapshot::create([
                        'user_id' => $user->id,
                        'luma_event_id' => $lumaEvent?->id,
                        'snapshot_date' => $date,
                        'earned_points' => $earned,
                        'redeemed_points' => $redeemed,
                        'balance' => $balance,
                        'meta' => [
                            'cumulative_earned' => $cumulativeEarned,
                            'cumulative_redeemed' => $cumulativeRedeemed,
                            'total_earned' => (int) ($row['Total_Earned'] ?? 0),
                            'total_redeemed' => abs((int) ($row['Total_Redeemed'] ?? 0)),
                        ],
                    ]);
                    $stats['snapshots_created']++;
                }
            }
        }
    }

    /**
     * Read an Excel sheet using Python.
     */
    protected function readExcelSheet(string $filePath, string $sheetName): array
    {
        $escapedPath = escapeshellarg($filePath);
        $escapedSheet = escapeshellarg($sheetName);

        $pythonScript = <<<PYTHON
import pandas as pd
import json
import sys
from datetime import datetime

try:
    df = pd.read_excel({$escapedPath}, sheet_name={$escapedSheet})
    # Replace NaN with None for JSON serialization
    df = df.where(pd.notnull(df), None)

    # Convert datetime columns to strings
    for col in df.columns:
        if df[col].dtype == 'datetime64[ns]':
            df[col] = df[col].apply(lambda x: x.strftime('%Y-%m-%d %H:%M:%S') if pd.notnull(x) else None)

    result = {
        'count': len(df),
        'rows': df.to_dict('records')
    }
    print(json.dumps(result))
except Exception as e:
    print(json.dumps({'error': str(e)}), file=sys.stderr)
    sys.exit(1)
PYTHON;

        $output = shell_exec("python3 -c " . escapeshellarg($pythonScript));

        if (!$output) {
            throw new \Exception("Failed to read Excel sheet: {$sheetName}");
        }

        $result = json_decode($output, true);

        if (isset($result['error'])) {
            throw new \Exception("Python error: {$result['error']}");
        }

        return $result;
    }

    /**
     * Sync guests from Luma API.
     */
    protected function syncGuestsFromLuma(LumaEvent $lumaEvent): int
    {
        $syncedCount = 0;
        $cursor = null;
        $hasMorePages = true;
        $pageCount = 0;
        $maxPages = 200;

        while ($hasMorePages && $pageCount < $maxPages) {
            $pageCount++;

            $response = $this->lumaService->getGuests($lumaEvent->luma_event_id, $this->apiKey, $cursor, true);

            $guests = $response['entries'] ?? [];

            if (empty($guests)) {
                break;
            }

            foreach ($guests as $guestData) {
                $this->upsertGuestFromLuma($lumaEvent, $guestData);
                $syncedCount++;
            }

            // Handle pagination
            $newCursor = $response['next_cursor'] ?? null;
            $hasMore = $response['has_more'] ?? false;

            if ($newCursor === $cursor) {
                break;
            }

            $cursor = $newCursor;
            $hasMorePages = $hasMore && !empty($cursor);
        }

        return $syncedCount;
    }

    /**
     * Upsert a guest record from Luma API data.
     */
    protected function upsertGuestFromLuma(LumaEvent $lumaEvent, array $guestData): void
    {
        $guestApiId = $guestData['api_id'] ?? null;
        $guestInfo = $guestData['guest'] ?? [];

        if (!$guestApiId) {
            return;
        }

        // Determine user type
        $userType = $this->determineUserType($guestInfo);

        // Check if guest already exists
        $guest = LumaGuest::where('guest_id', $guestApiId)->first();

        $guestAttributes = [
            'luma_event_id' => $lumaEvent->id,
            'guest_id' => $guestApiId,
            'luma_user_id' => $guestInfo['user_id'] ?? null,
            'approval_status' => $guestInfo['approval_status'] ?? 'pending',
            'user_name' => $guestInfo['name'] ?? '',
            'user_first_name' => $guestInfo['first_name'] ?? '',
            'user_last_name' => $guestInfo['last_name'] ?? '',
            'user_email' => $guestInfo['email'] ?? '',
            'registered_at' => $guestInfo['registered_at'] ?? null,
            'checked_in_at' => $guestInfo['checked_in_at'] ?? null,
            'registration_answers' => $guestInfo['registration_answers'] ?? null,
            'event_tickets' => $guestInfo['event_tickets'] ?? null,
            'event_ticket' => $guestInfo['event_ticket'] ?? null,
            'event_ticket_orders' => $guestInfo['event_ticket_orders'] ?? null,
            'raw_data' => $guestData,
        ];

        if ($guest) {
            // Update existing guest
            $guest->update($guestAttributes);

            // Create user if doesn't exist
            if (!$guest->user_id) {
                $user = $this->createUser($guestInfo, $userType);
                $guest->update(['user_id' => $user->id]);
            }
        } else {
            // Create new user
            $user = $this->createUser($guestInfo, $userType);

            // Create new guest
            $guestAttributes['user_id'] = $user->id;
            LumaGuest::create($guestAttributes);
        }
    }

    /**
     * Display import statistics.
     */
    protected function displayStats(array $stats): void
    {
        $this->newLine();
        $this->line('📊 Import Statistics:');
        $rows = [
            ['Users Created', $stats['users_created']],
            ['Users Updated', $stats['users_updated']],
        ];

        if ($stats['guests_synced_from_luma'] > 0) {
            $rows[] = ['Guests Synced from Luma API', $stats['guests_synced_from_luma']];
        }

        $rows[] = ['Luma Guests Created from Excel', $stats['guests_created']];
        $rows[] = ['Wallet Addresses Created', $stats['wallets_created']];
        $rows[] = ['Point Snapshots Created', $stats['snapshots_created']];

        $this->table(['Category', 'Count'], $rows);
    }
}