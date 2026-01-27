<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            DocumentTemplateSeeder::class,
            FiscalResourceSeeder::class,
            CalculatorSeeder::class,
        ]);
        
        $this->command->info('✅ All seeders completed successfully!');
        $this->command->info('📊 Database populated with:');
        $this->command->info('   - Document Templates: ~196 templates (14 per type × 14 countries)');
        $this->command->info('   - Fiscal Resources: ~210 resources (15 per country)');
        $this->command->info('   - Calculators: ~56 calculators (4 types × 14 countries)');
        $this->command->info('   Total: ~462 records created');
    }
}
