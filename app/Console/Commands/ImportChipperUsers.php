<?php

namespace App\Console\Commands;

use App\Actions\User\CreateUser;
use App\Models\User;
use Cerbero\JsonParser\JsonParser;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

class ImportChipperUsers extends Command implements PromptsForMissingInput
{
    protected $signature = 'app:import-chipper-users {json-url} {--limit=}';

    protected $description = 'Import users from a JSON URL using streaming (e.g., https://jsonplaceholder.typicode.com/users)';

    public function handle(): int
    {
        $jsonUrl = $this->argument('json-url');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        $this->info("Fetching users from: {$jsonUrl}");

        try {
            $imported = 0;
            $skipped = 0;
            $processed = 0;

            $this->info('Importing users (streaming)...');

            // Create progress bar (we don't know the total count, so use indeterminate mode)
            $bar = $this->output->createProgressBar();
            $bar->setFormat(' %current% user(s) processed');
            $bar->start();

            JsonParser::parse($jsonUrl)->traverse(function (mixed $userData) use ($limit, &$imported, &$skipped, &$processed, $bar) {
                if ($limit > 0 && $processed >= $limit) {
                    return;
                }

                $processed++;

                // Convert object to array if needed
                if (is_object($userData)) {
                    $userData = json_decode(json_encode($userData), true);
                }

                // Skip if required fields are missing
                if (blank($userData['name'] ?? null) || blank($userData['email'] ?? null)) {
                    $skipped++;
                    $bar->advance();

                    return;
                }

                if (User::where('email', $userData['email'])->exists()) {
                    $skipped++;
                    $bar->advance();

                    return;
                }

                dispatch(fn () => (new CreateUser())(
                    name: $userData['name'],
                    email: $userData['email'],
                    password: 'password',
                    verifyEmail: ! config('app.must_verify_email'),
                ));

                $imported++;
                $bar->advance();
            });

            $bar->finish();
            $this->newLine(2);

            $this->info("Successfully imported {$imported} user(s).");

            if ($skipped > 0) {
                $this->warn("Skipped {$skipped} user(s) (duplicates or missing data).");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("An error occurred: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
