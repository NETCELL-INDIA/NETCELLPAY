<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixDuplicateRequestOrderIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --fix : actually perform fixes; otherwise only report
     * --dry  : alias for not fixing
     * --limit=N : limit number of duplicate groups to fix/report
     *
     * @var string
     */
    protected $signature = 'reports:fix-duplicates {--fix : Apply fixes} {--limit=0 : Limit number of groups to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan reports table for duplicate request_order_id and optionally nullify duplicates (do not delete successful transactions)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning reports table for duplicate request_order_id...');

        // Find duplicates where request_order_id is not NULL/empty
        $duplicatesQuery = DB::table('reports')
            ->select('request_order_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('request_order_id')
            ->where('request_order_id', '!=', '')
            ->groupBy('request_order_id')
            ->having('cnt', '>', 1)
            ->orderBy('cnt', 'desc');

        $limit = (int)$this->option('limit');
        if ($limit > 0) {
            $duplicates = $duplicatesQuery->limit($limit)->get();
        } else {
            $duplicates = $duplicatesQuery->get();
        }

        $totalGroups = $duplicates->count();
        if ($totalGroups == 0) {
            $this->info('No duplicate request_order_id found. Safe to run migration.');
            return 0;
        }

        $this->warn("Found {$totalGroups} duplicate request_order_id groups.\n");

        $doFix = $this->option('fix');

        foreach ($duplicates as $group) {
            $ro = $group->request_order_id;
            $cnt = $group->cnt;

            $this->line("request_order_id: {$ro} (count: {$cnt})");

            // Fetch all rows for this request_order_id
            $rows = DB::table('reports')
                ->where('request_order_id', $ro)
                ->orderBy('id', 'asc')
                ->get();

            // Determine canonical row to keep request_order_id on
            // Prefer a Success/Transferred/Refunded (final) row; if multiple, pick earliest id
            $finalStatuses = ['Success', 'Transferred', 'Refunded'];
            $canonical = null;

            foreach ($rows as $r) {
                if (in_array($r->status, $finalStatuses)) {
                    $canonical = $r;
                    break;
                }
            }

            if (!$canonical) {
                // No final status row found; pick earliest created_at or lowest id
                $canonical = $rows->first();
            }

            $this->line(" -> canonical id: {$canonical->id} (status: {$canonical->status})");

            // Collect ids to nullify (all except canonical)
            $toNull = [];
            foreach ($rows as $r) {
                if ($r->id != $canonical->id) {
                    $toNull[] = $r->id;
                }
            }

            $this->line(' -> duplicate ids: ' . implode(',', $toNull));

            if ($doFix) {
                DB::beginTransaction();
                try {
                    // For each duplicate id, nullify request_order_id and add a remark prefix so we keep trace
                    foreach ($toNull as $dupId) {
                        // Do not modify rows that are in final statuses and are canonical preservation must respect that
                        $row = DB::table('reports')->where('id', $dupId)->first();
                        if (!$row) continue;

                        // If duplicate row itself is a successful-type status, we should not nullify it; instead prefer to nullify others.
                        // But logic above chose canonical, so duplicate here should not be final; still check to be safe.
                        if (in_array($row->status, $finalStatuses)) {
                            // Skip nullifying this final row — make canonical this one and nullify previous canonical
                            // Update canonical to this row and mark previous canonical to nullify instead
                            $this->warn("   Note: duplicate id {$dupId} has final status {$row->status}. Making it canonical instead.");
                            // previous canonical becomes duplicate
                            $prevCanonicalId = $canonical->id;
                            $canonical = $row;
                            // set prev canonical to nullify below
                            // we will include prevCanonicalId in the toNull list if not already
                            if (!in_array($prevCanonicalId, $toNull)) {
                                $toNull[] = $prevCanonicalId;
                            }
                            continue;
                        }

                        // Nullify request_order_id for duplicate row
                        DB::table('reports')->where('id', $dupId)->update([
                            'request_order_id' => null,
                            'remark' => ('[DUPLICATE_REQID_NULLIFIED:' . $canonical->id . '] ' . ($row->remark ?? '')),
                            'updated_at' => Carbon::now(),
                        ]);

                        // Log this action in apilogs for audit
                        DB::table('apilogs')->insert([
                            'url' => 'fix-duplicate-request_order_id',
                            'modal' => 'FixDuplicateRequestOrderIds',
                            'txnid' => $dupId,
                            'header' => json_encode([]),
                            'request' => json_encode(['old_request_order_id' => $ro, 'canonical_id' => $canonical->id]),
                            'response' => 'nullified duplicate request_order_id',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }

                    DB::commit();
                    $this->info(' -> fixed group');
                } catch (\Throwable $e) {
                    DB::rollBack();
                    $this->error(' -> error fixing group: ' . $e->getMessage());
                }
            } else {
                $this->line(' -> run with --fix to nullify duplicate request_order_id for the non-canonical rows');
            }

            $this->line('');
        }

        $this->info('Done scanning.');
        if (!$doFix) {
            $this->warn('No changes made. Re-run with --fix to apply fixes.');
        }

        return 0;
    }
}
