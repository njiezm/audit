<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_categories', function (Blueprint $table) {
            $table->unsignedInteger('position')->default(0)->after('audit_id');
            $table->unsignedTinyInteger('weight')->default(1)->after('score');
            $table->string('priority')->nullable()->after('recommendations');
            $table->date('due_on')->nullable()->after('priority');
            $table->string('owner')->nullable()->after('due_on');

            $table->index(['audit_id', 'position']);
        });

        // Position initiale = ordre d'insertion historique (par id).
        $rows = DB::table('audit_categories')->orderBy('audit_id')->orderBy('id')->get(['id', 'audit_id']);
        $counters = [];

        foreach ($rows as $row) {
            $counters[$row->audit_id] = ($counters[$row->audit_id] ?? -1) + 1;
            DB::table('audit_categories')->where('id', $row->id)->update(['position' => $counters[$row->audit_id]]);
        }
    }

    public function down(): void
    {
        Schema::table('audit_categories', function (Blueprint $table) {
            $table->dropIndex(['audit_id', 'position']);
            $table->dropColumn(['position', 'weight', 'priority', 'due_on', 'owner']);
        });
    }
};
