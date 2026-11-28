<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('complaints')) {
            DB::statement("ALTER TABLE complaints MODIFY status VARCHAR(40) NOT NULL DEFAULT 'Open'");
            Schema::table('complaints', function (Blueprint $table) {
                if (! Schema::hasColumn('complaints', 'priority')) {
                    $table->string('priority', 16)->default('normal');
                }
                if (! Schema::hasColumn('complaints', 'assigned_to')) {
                    $table->unsignedBigInteger('assigned_to')->nullable()->index();
                }
                if (! Schema::hasColumn('complaints', 'category')) {
                    $table->string('category', 40)->nullable();
                }
                if (! Schema::hasColumn('complaints', 'description')) {
                    $table->text('description')->nullable();
                }
                if (! Schema::hasColumn('complaints', 'intended_number')) {
                    $table->string('intended_number', 30)->nullable();
                }
                if (! Schema::hasColumn('complaints', 'duplicate_report_id')) {
                    $table->unsignedBigInteger('duplicate_report_id')->nullable();
                }
                if (! Schema::hasColumn('complaints', 'resolved_at')) {
                    $table->timestamp('resolved_at')->nullable();
                }
                if (! Schema::hasColumn('complaints', 'closed_at')) {
                    $table->timestamp('closed_at')->nullable();
                }
                if (! Schema::hasColumn('complaints', 'rejected_reason')) {
                    $table->string('rejected_reason', 255)->nullable();
                }
            });
        }

        if (! Schema::hasTable('complaint_categories')) {
            Schema::create('complaint_categories', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('label', 80);
                $table->string('group_name', 20)->default('recharge');
                $table->timestamps();
            });
            $now = now();
            foreach ([
                ['recharge_failed', 'Recharge Failed', 'recharge'],
                ['recharge_pending', 'Recharge Pending', 'recharge'],
                ['success_not_received', 'Success but Not Received', 'recharge'],
                ['wrong_number', 'Wrong Number', 'recharge'],
                ['duplicate_recharge', 'Duplicate Recharge', 'recharge'],
                ['refund_not_received', 'Refund Not Received', 'recharge'],
                ['dth', 'DTH Complaint', 'dth'],
                ['other', 'Other', 'recharge'],
            ] as $row) {
                DB::table('complaint_categories')->insert([
                    'code' => $row[0],
                    'label' => $row[1],
                    'group_name' => $row[2],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('complaint_messages')) {
            Schema::create('complaint_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('complaint_id')->index();
                $table->string('sender_role', 20);
                $table->unsignedBigInteger('sender_id')->default(0);
                $table->text('body');
                $table->string('visibility', 20)->default('customer');
                $table->string('delivery_status', 20)->nullable();
                $table->string('delivery_error', 255)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('complaint_attachments')) {
            Schema::create('complaint_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('complaint_id')->index();
                $table->unsignedBigInteger('message_id')->nullable();
                $table->string('stored_path', 255);
                $table->string('original_name', 180);
                $table->string('mime', 80);
                $table->unsignedInteger('size')->default(0);
                $table->unsignedBigInteger('uploaded_by')->default(0);
                $table->string('visibility', 20)->default('customer');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('complaint_assignments')) {
            Schema::create('complaint_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('complaint_id')->index();
                $table->unsignedBigInteger('staff_id');
                $table->unsignedBigInteger('assigned_by')->default(0);
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('complaint_status_history')) {
            Schema::create('complaint_status_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('complaint_id')->index();
                $table->string('previous_status', 40);
                $table->string('new_status', 40);
                $table->unsignedBigInteger('admin_id')->default(0);
                $table->string('remarks', 255)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['complaint_id', 'created_at'], 'complaint_status_history_lookup');
            });
        }

        if (! Schema::hasTable('complaint_feedback')) {
            Schema::create('complaint_feedback', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('complaint_id')->index();
                $table->unsignedBigInteger('user_id')->default(0);
                $table->unsignedTinyInteger('rating')->nullable();
                $table->string('comment', 500)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_feedback');
        Schema::dropIfExists('complaint_status_history');
        Schema::dropIfExists('complaint_assignments');
        Schema::dropIfExists('complaint_attachments');
        Schema::dropIfExists('complaint_messages');
        Schema::dropIfExists('complaint_categories');

        if (! Schema::hasTable('complaints')) {
            return;
        }
        Schema::table('complaints', function (Blueprint $table) {
            foreach ([
                'rejected_reason', 'closed_at', 'resolved_at', 'duplicate_report_id',
                'intended_number', 'description', 'category', 'assigned_to', 'priority',
            ] as $column) {
                if (Schema::hasColumn('complaints', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
