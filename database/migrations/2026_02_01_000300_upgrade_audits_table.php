<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Nouvelles colonnes. `audit_id` (la référence métier) est renommé en
        //    `reference` pour lever l'ambiguïté avec la clé étrangère `audit_id`
        //    de la table audit_categories.
        Schema::table('audits', function (Blueprint $table) {
            $table->renameColumn('audit_id', 'reference');
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();

            $table->string('title')->nullable()->after('reference');
            $table->string('status')->default('draft')->after('title');
            $table->string('scoring_mode')->default('weighted')->after('status');
            $table->decimal('global_score', 4, 2)->nullable()->after('scoring_mode');

            $table->date('follow_up_on')->nullable()->after('audit_date');

            // Intégrité / vérification
            $table->string('content_hash', 64)->nullable()->after('signed_by');
            $table->string('verification_code', 24)->nullable()->unique()->after('content_hash');
            $table->foreignId('signed_by_user_id')->nullable()->after('verification_code')->constrained('users')->nullOnDelete();

            // Contre-signature client
            $table->boolean('is_countersigned')->default(false)->after('signed_by_user_id');
            $table->timestamp('countersigned_at')->nullable()->after('is_countersigned');
            $table->string('countersigned_by')->nullable()->after('countersigned_at');

            $table->timestamp('sent_at')->nullable()->after('countersigned_by');
            $table->timestamp('archived_at')->nullable()->after('sent_at');

            $table->softDeletes();

            $table->index('status');
            $table->index('client_name');
            $table->index('audit_date');
            $table->index('created_at');
        });

        // 2. Backfill : créer un client pour chaque client_name distinct déjà saisi.
        $names = DB::table('audits')->select('client_name')->distinct()->pluck('client_name');
        $now = now();

        foreach ($names as $name) {
            if (blank($name)) {
                continue;
            }

            $slug = Str::slug($name) ?: Str::random(8);
            $clientId = DB::table('clients')->insertGetId([
                'name' => $name,
                'slug' => $slug,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('audits')->where('client_name', $name)->update(['client_id' => $clientId]);
        }

        // 3. Les audits déjà signés passent au statut correspondant.
        DB::table('audits')->where('is_signed', true)->update(['status' => 'signed']);
        DB::table('audits')->where('is_signed', false)->update(['status' => 'draft']);
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['signed_by_user_id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'client_id', 'user_id', 'created_by', 'updated_by', 'title', 'status',
                'scoring_mode', 'global_score', 'follow_up_on', 'content_hash',
                'verification_code', 'signed_by_user_id', 'is_countersigned',
                'countersigned_at', 'countersigned_by', 'sent_at', 'archived_at',
            ]);
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->renameColumn('reference', 'audit_id');
        });
    }
};
