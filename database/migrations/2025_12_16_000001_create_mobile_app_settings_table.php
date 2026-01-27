<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mobile_app_settings', function (Blueprint $table) {
            $table->id();
            
            // App Version Control
            $table->string('android_version')->default('1.0.0');
            $table->integer('android_build_number')->default(1);
            $table->string('ios_version')->default('1.0.0');
            $table->integer('ios_build_number')->default(1);
            
            // Force Update
            $table->boolean('force_update_android')->default(false);
            $table->string('min_android_version')->default('1.0.0');
            $table->boolean('force_update_ios')->default(false);
            $table->string('min_ios_version')->default('1.0.0');
            
            // Maintenance Mode
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            $table->timestamp('maintenance_start')->nullable();
            $table->timestamp('maintenance_end')->nullable();
            
            // API Configuration
            $table->text('openai_api_key')->nullable();
            $table->text('pinecone_api_key')->nullable();
            $table->text('pinecone_index_name')->nullable();
            $table->text('pinecone_environment')->nullable();
            $table->text('flutterwave_public_key')->nullable();
            $table->text('flutterwave_secret_key')->nullable();
            $table->string('flutterwave_environment')->default('test'); // test or live
            
            // Firebase Configuration
            $table->text('firebase_server_key')->nullable();
            $table->text('firebase_messaging_sender_id')->nullable();
            
            // Features Toggle
            $table->boolean('chat_enabled')->default(true);
            $table->boolean('documents_enabled')->default(true);
            $table->boolean('tools_enabled')->default(true);
            $table->boolean('referral_enabled')->default(true);
            
            // Limits & Quotas (Default per plan)
            $table->json('free_plan_limits')->nullable(); // {"searches": 5, "analyses": 2}
            $table->json('student_plan_limits')->nullable();
            $table->json('professional_plan_limits')->nullable();
            $table->json('cabinet_plan_limits')->nullable();
            
            // App URLs
            $table->string('play_store_url')->nullable();
            $table->string('app_store_url')->nullable();
            $table->string('privacy_policy_url')->nullable();
            $table->string('terms_of_service_url')->nullable();
            
            // Support & Help
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('whatsapp_number')->nullable();
            
            $table->timestamps();
        });

        // Insert default settings
        DB::table('mobile_app_settings')->insert([
            'android_version' => '1.0.0',
            'android_build_number' => 1,
            'ios_version' => '1.0.0',
            'ios_build_number' => 1,
            'force_update_android' => false,
            'min_android_version' => '1.0.0',
            'force_update_ios' => false,
            'min_ios_version' => '1.0.0',
            'maintenance_mode' => false,
            'flutterwave_environment' => 'test',
            'chat_enabled' => true,
            'documents_enabled' => true,
            'tools_enabled' => true,
            'referral_enabled' => true,
            'free_plan_limits' => json_encode([
                'searches' => 5,
                'analyses' => 2,
                'downloads' => 0,
                'messages_per_day' => 10
            ]),
            'student_plan_limits' => json_encode([
                'searches' => 50,
                'analyses' => 20,
                'downloads' => 30,
                'messages_per_day' => 100
            ]),
            'professional_plan_limits' => json_encode([
                'searches' => 200,
                'analyses' => 100,
                'downloads' => 150,
                'messages_per_day' => 500
            ]),
            'cabinet_plan_limits' => json_encode([
                'searches' => -1, // unlimited
                'analyses' => -1,
                'downloads' => -1,
                'messages_per_day' => -1
            ]),
            'support_email' => 'support@dossypro.com',
            'support_phone' => '+225 07 00 00 00 00',
            'privacy_policy_url' => 'https://dossypro.com/privacy',
            'terms_of_service_url' => 'https://dossypro.com/terms',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mobile_app_settings');
    }
};
