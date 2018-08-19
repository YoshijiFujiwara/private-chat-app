<?php

use Illuminate\Database\Seeder;
use App\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);

        factory(User::class)->create([
            'email' => 'yfbiology2@gmail.com',
            'name' => 'yoshiji'
        ]);
        factory(User::class)->create([
            'email' => 'yfbiology2+2@gmail.com',
            'name' => 'yoshiji2'
        ]);
        factory(User::class)->create([
            'email' => 'fsdafas@gmail.com',
            'name' => 'satoshi'
        ]);
    }
}
