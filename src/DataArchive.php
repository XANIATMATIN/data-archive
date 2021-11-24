<?php

namespace MatinUtils\DataArchive;

use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DataArchive
{
    public function handle(string $connection,  array $archivable, string $messageHandler = '')
    {
        app('log')->info('start  DataArchive');
        $messageHandler = $this->checkMessageHandlerFunction($messageHandler);

        $tableName = $archivable['table'] ?? null;
        $daysNumber = (int) $archivable['daysNumber'] ?? 1;
        $conditionColumn = $archivable['conditionColumn'] ?? 'created_at';

        $time =  Carbon::now()->subDays($daysNumber);
        if (($archivable['columnType'] ?? null) == 'timeStamp') {
            $time =  $time->timestamp;
        }

        if (!$this->checkTableColumnValidity($tableName, $conditionColumn, $messageHandler)) return;

        $items = \DB::table($tableName)->where($conditionColumn, '<', $time)->get();
        $items = json_decode(json_encode($items), true);

        if (count($items) < 1) {
            return $messageHandler('No Items To move.');
        }

        $this->creatTableIfDoesntExist($connection, $tableName, $messageHandler);

        $insertables = array_map(function ($item) {
            unset($item['id']);
            return $item;
        }, $items);

        try {
            foreach (array_chunk($insertables, 50) as $insertable) {
                \DB::connection($connection)->table($tableName)->insert($insertable);
            }
        } catch (\Throwable $th) {
            $messageHandler("Error: Unable to move data. " . $th->getMessage());
        }

        \DB::table($tableName)->where($conditionColumn, '<', $time)->delete();

        $messageHandler(count($insertables) . " items moved from $tableName table");
    }

    protected function creatTableIfDoesntExist($connection, $tableName, $messageHandler)
    {
        if (!Schema::connection($connection)->hasTable($tableName)) {

            try {
                $tableStructure =  json_decode(json_encode(\DB::select("show Create table $tableName")[0]), true);
                \DB::connection($connection)->statement($tableStructure['Create Table']);
            } catch (\Throwable $th) {
                $messageHandler("Error: Unable to create table. " . $th->getMessage());
            }
        }
    }

    protected function checkTableColumnValidity($tableName, $conditionColumn, $messageHandler)
    {
        if (empty($tableName)) {
            $messageHandler('Error: table is required');
            return false;
        }

        if (!Schema::hasTable($tableName)) {
            $messageHandler("Error: Table $tableName does not exist.");
            return false;
        }

        if (!Schema::hasColumn($tableName, $conditionColumn)) {
            $messageHandler("Error: Table $tableName does not have column $conditionColumn.");
            return false;
        }
        return true;
    }

    protected function checkMessageHandlerFunction($userFunction)
    {
        if (function_exists($userFunction)) {
            return $userFunction;
        }
        return function () {
        };
    }
}
