<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Service\ZohoReportService;
use Google\Exception;
use Google\Service\Drive\DriveFile;
use Illuminate\Console\Command;

class GenerateZohoSpreadsheet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoho:generate-spreadsheet-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private \Google_Service_Drive $googleDriveService;

    /**
     * Create a new command instance.
     *
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct();

        $client = new \Google_Client();
        $client->setApplicationName('Google Sheets API');
        $client->setScopes([\Google_Service_Drive::DRIVE]);
        $client->setAccessType('offline');
        $path = config_path('neils-project-369213-07fb946eb492.json');
        $client->setAuthConfig($path);

        $this->googleDriveService = new \Google_Service_Drive($client);
    }

    function createFile($data): DriveFile|null
    {
        try {
            $emptyFile = new \Google_Service_Drive_DriveFile();
            $emptyFile->setWritersCanShare(true);
            $emptyFile->setName('report' . date('Y-m-d H:i:s'));
            $emptyFile->parents = ['1ZtZSiDcdFEUDKnkVh0SyXVnBq9v3jqDI'];

            return $this->googleDriveService->files->create($emptyFile, array(
                'data' => $data,
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadType' => 'multipart',
                'supportsAllDrives' => true,
                'ignoreDefaultVisibility' => true,
            ));
        } catch (\Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }

        return null;
    }

    /**
     * Execute the console command.
     *
     * @param ZohoReportService $zohoReportService
     * @return int
     */
    public function handle(ZohoReportService $zohoReportService): int
    {
        $settings = $zohoReportService->getSettings();

        ob_start();
        $zohoReportService->outputReport($settings['planToLaunch'] ?? [], $settings['allocateToStaff'] ?? []);
        $content = ob_get_clean();

        $fileId = '1TIw2k6qTlCTPEcWFryM9aXPNR8hyquoK';
        $this->updateFile($fileId, $content);

        return 0;
    }

    function updateFile($fileId, $data): void
    {
        try {
            $emptyFile = new \Google_Service_Drive_DriveFile();
            $emptyFile->setName('report' . date('Y-m-d H:i:s'));
            $this->googleDriveService->files->update($fileId, $emptyFile, array(
                'data' => $data,
                'mimeType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'uploadType' => 'multipart',
                'supportsAllDrives' => true,
            ));
        } catch (\Exception $e) {
            print "An error occurred: " . $e->getMessage();
        }
    }
}
