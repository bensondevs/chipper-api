<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ImportChipperUsersTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        // Clean up temporary files
        $files = glob(sys_get_temp_dir() . '/chipper_users_test_*.json');
        foreach ($files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    protected function createTempJsonFile(array $data): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'chipper_users_test_');
        file_put_contents($tempFile, json_encode($data));

        return 'file://' . $tempFile;
    }

    public function test_it_imports_users_from_json_url()
    {
        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
            [
                'id' => 2,
                'name' => 'Ervin Howell',
                'email' => 'Shanna@melissa.tv',
                'username' => 'Antonette',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])
            ->expectsOutput("Fetching users from: {$fileUrl}")
            ->expectsOutput('Importing users (streaming)...')
            ->expectsOutput('Successfully imported 2 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'name' => 'Leanne Graham',
            'email' => 'Sincere@april.biz',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Ervin Howell',
            'email' => 'Shanna@melissa.tv',
        ]);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_it_respects_limit_option()
    {
        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
            [
                'id' => 2,
                'name' => 'Ervin Howell',
                'email' => 'Shanna@melissa.tv',
                'username' => 'Antonette',
            ],
            [
                'id' => 3,
                'name' => 'Clementine Bauch',
                'email' => 'Nathan@yesenia.net',
                'username' => 'Samantha',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
            '--limit' => 2,
        ])
            ->expectsOutput('Importing users (streaming)...')
            ->expectsOutput('Successfully imported 2 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'email' => 'Sincere@april.biz',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'Shanna@melissa.tv',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'Nathan@yesenia.net',
        ]);
    }

    public function test_it_skips_duplicate_users()
    {
        // Create an existing user
        User::factory()->create([
            'email' => 'Sincere@april.biz',
            'name' => 'Existing User',
        ]);

        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
            [
                'id' => 2,
                'name' => 'Ervin Howell',
                'email' => 'Shanna@melissa.tv',
                'username' => 'Antonette',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])
            ->expectsOutput('Successfully imported 1 user(s).')
            ->expectsOutput('Skipped 1 user(s) (duplicates or missing data).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 2); // 1 existing + 1 imported
        $this->assertDatabaseHas('users', [
            'email' => 'Shanna@melissa.tv',
        ]);
        // Verify the existing user wasn't updated
        $this->assertDatabaseHas('users', [
            'email' => 'Sincere@april.biz',
            'name' => 'Existing User',
        ]);
    }

    public function test_it_skips_users_with_missing_required_fields()
    {
        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
            [
                'id' => 2,
                'name' => '', // Empty name
                'email' => 'Shanna@melissa.tv',
                'username' => 'Antonette',
            ],
            [
                'id' => 3,
                'name' => 'Clementine Bauch',
                'email' => '', // Empty email
                'username' => 'Samantha',
            ],
            [
                'id' => 4,
                'name' => null, // Null name
                'email' => 'test@example.com',
                'username' => 'testuser',
            ],
            [
                'id' => 5,
                // Missing name key entirely
                'email' => 'missingname@example.com',
                'username' => 'missingname',
            ],
            [
                'id' => 6,
                'name' => 'Missing Email',
                // Missing email key entirely
                'username' => 'missingemail',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])
            ->expectsOutput('Successfully imported 1 user(s).')
            ->expectsOutput('Skipped 5 user(s) (duplicates or missing data).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'Sincere@april.biz',
        ]);
    }

    public function test_it_handles_empty_json_array()
    {
        $fileUrl = $this->createTempJsonFile([]);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])
            ->expectsOutput('Importing users (streaming)...')
            ->expectsOutput('Successfully imported 0 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_handles_invalid_json_format()
    {
        // Create a file with invalid JSON (not an array)
        $tempFile = tempnam(sys_get_temp_dir(), 'chipper_users_test_');
        file_put_contents($tempFile, json_encode(['id' => 1, 'name' => 'Test'])); // Object, not array
        $fileUrl = 'file://' . $tempFile;

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])
            ->expectsOutput('Importing users (streaming)...')
            ->expectsOutput('Successfully imported 0 user(s).')
            ->assertExitCode(0); // cerbero/json-parser will just iterate 0 times for non-array

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_handles_http_error()
    {
        // This test will actually make an HTTP request since we can't fake Guzzle easily
        // We'll use a non-existent URL
        $this->artisan('app:import-chipper-users', [
            'json-url' => 'https://invalid-url-that-does-not-exist-12345.com/users',
        ])
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_handles_file_not_found()
    {
        $this->artisan('app:import-chipper-users', [
            'json-url' => 'file:///nonexistent/path/to/file.json',
        ])
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_imported_users_have_default_password()
    {
        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])->assertExitCode(0);

        $user = User::where('email', 'Sincere@april.biz')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_imported_users_have_verified_email()
    {
        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])->assertExitCode(0);

        $user = User::where('email', 'Sincere@april.biz')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_it_processes_all_users_when_limit_is_zero()
    {
        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
            [
                'id' => 2,
                'name' => 'Ervin Howell',
                'email' => 'Shanna@melissa.tv',
                'username' => 'Antonette',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
            '--limit' => 0,
        ])
            ->expectsOutput('Successfully imported 2 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_it_processes_all_users_when_limit_is_not_provided()
    {
        $jsonData = [
            [
                'id' => 1,
                'name' => 'Leanne Graham',
                'email' => 'Sincere@april.biz',
                'username' => 'Bret',
            ],
            [
                'id' => 2,
                'name' => 'Ervin Howell',
                'email' => 'Shanna@melissa.tv',
                'username' => 'Antonette',
            ],
        ];

        $fileUrl = $this->createTempJsonFile($jsonData);

        $this->artisan('app:import-chipper-users', [
            'json-url' => $fileUrl,
        ])
            ->expectsOutput('Successfully imported 2 user(s).')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 2);
    }
}
