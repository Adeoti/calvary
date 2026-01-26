<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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

            // Make sure doctrine/dbal is installed for change()
            $table->json('file_path')->nullable()->change();

            // ✅ Normal index only (NO uniqueness)
            $table->index(
                ['result_root_id', 'class_id', 'subject_id', 'entry_type'],
                'ru_root_class_subject_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('result_uploads', function (Blueprint $table) {

            // Drop index
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
