<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Mettre à jour les prix de mobile_subscription_plans 
     * pour correspondre à mobile_app_plans
     */
    public function up(): void
    {
        // Update prices to match mobile_app_plans
        DB::table('mobile_subscription_plans')->where('slug', 'gratuit')->update([
            'price_monthly' => 0,
            'price_yearly' => 0,
        ]);

        DB::table('mobile_subscription_plans')->where('slug', 'etudiant')->update([
            'price_monthly' => 2000,
            'price_yearly' => 22000,
        ]);

        DB::table('mobile_subscription_plans')->where('slug', 'pro')->update([
            'price_monthly' => 5000,
            'price_yearly' => 55000,
        ]);

        DB::table('mobile_subscription_plans')->where('slug', 'cabinet-entreprise')->update([
            'price_monthly' => 20000,
            'price_yearly' => 220000,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('mobile_subscription_plans')->where('slug', 'gratuit')->update([
            'price_monthly' => 0,
            'price_yearly' => 0,
        ]);

        DB::table('mobile_subscription_plans')->where('slug', 'etudiant')->update([
            'price_monthly' => 2000,
            'price_yearly' => 22000,
        ]);

        DB::table('mobile_subscription_plans')->where('slug', 'pro')->update([
            'price_monthly' => 5000,
            'price_yearly' => 55000,
        ]);

        DB::table('mobile_subscription_plans')->where('slug', 'cabinet-entreprise')->update([
            'price_monthly' => 15000,
            'price_yearly' => 165000,
        ]);
    }
};
