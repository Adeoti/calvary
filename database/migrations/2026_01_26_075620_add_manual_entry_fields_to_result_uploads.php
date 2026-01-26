<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('result_uploads', function (Blueprint $table) {

            if (!Schema::hasColumn('result_uploads', 'entry_type')) {
                $table->enum('entry_type', ['csv', 'manual'])
                    ->default('csv')
                    ->after('class_id');
            }

            if (!Schema::hasColumn('result_uploads', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('result_uploads', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            }

            // Change column (requires doctrine/dbal)
            $table->json('file_path')->nullable()->change();
        });

        // ✅ Drop index if it already exists (prevents duplicate key error)
        DB::statement("
            DROP INDEX ru_root_class_subject_type_idx
            ON result_uploads
        ");

        // ✅ Re-add index cleanly (NO uniqueness)
        Schema::table('result_uploads', function (Blueprint $table) {
            $table->index(
                ['result_root_id', 'class_id', 'subject_id', 'entry_type'],
                'ru_root_class_subject_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('result_uploads', function (Blueprint $table) {

            $table->dropIndex('ru_root_class_subject_type_idx');

            if (Schema::hasColumn('result_uploads', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }

            if (Schema::hasColumn('result_uploads', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('result_uploads', 'entry_type')) {
                $table->dropColumn('entry_type');
            }
        });
    }
};
