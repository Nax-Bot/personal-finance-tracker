<?php

namespace App\Controllers;

use App\Queries\GetMonthlySummary;
use PDO;

use App\Controllers\JsonResponse;

class SummaryController
{
    public function __construct(
        private PDO $connection
    ) {}

    public function index(): void
    {
        $month = $_GET['month'] ?? date('Y-m');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            JsonResponse::send([
                'error' => 'Invalid month format. Use YYYY-MM.'
            ], 422);

            return;
        }

        try {
            $query = new GetMonthlySummary(
                $this->connection
            );

            $summary = $query->execute($month);

            JsonResponse::send($summary);
        } catch (\Throwable $e) {
            JsonResponse::send([
                'error' => 'Unable to retrieve monthly summary.'
            ], 500);
        }
    }
}
