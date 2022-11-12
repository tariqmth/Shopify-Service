<?php

namespace App\Console\Commands;

use App\Updaters\Updater;
use App\Updaters\UpdaterFor10505CustomerDuplicates;
use App\Updaters\UpdaterFor10720GiftCardGateway;
use App\Updaters\UpdaterFor10729PrimaryLocation;
use App\Updaters\UpdaterFor13535SeoTitle;
use App\Updaters\UpdaterFor7995PendingFulfillments;
use App\Updaters\UpdaterFor9404MultiCurrency;
use Illuminate\Console\Command;
use App\Models\Update as UpdateModel;

class Update extends Command
{
    protected $updaterFor9404MultiCurrency;
    protected $updaterFor10505CustomerDuplicates;
    protected $updaterFor10729PrimaryLocation;
    protected $updaterFor13535SeoTitle;
    protected $updaterFor7995PendingFulfillments;

    public function __construct(
        UpdaterFor9404MultiCurrency $updaterFor9404MultiCurrency,
        UpdaterFor10505CustomerDuplicates $updaterFor10505CustomerDuplicates,
        UpdaterFor10729PrimaryLocation $updaterFor10729PrimaryLocation,
        UpdaterFor13535SeoTitle $updaterFor13535SeoTitle,
        UpdaterFor7995PendingFulfillments $updaterFor7995PendingFulfillments
    ) {
        parent::__construct();
        $this->updaterFor9404MultiCurrency = $updaterFor9404MultiCurrency;
        $this->updaterFor10505CustomerDuplicates = $updaterFor10505CustomerDuplicates;
        $this->updaterFor10729PrimaryLocation = $updaterFor10729PrimaryLocation;
        $this->updaterFor13535SeoTitle = $updaterFor13535SeoTitle;
        $this->updaterFor7995PendingFulfillments = $updaterFor7995PendingFulfillments;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-connector:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run update tasks relevant to latest deployment';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $completedUpdates = UpdateModel::all()->pluck('name')->toArray();
        $updaters = [
            $this->updaterFor9404MultiCurrency,
            $this->updaterFor10505CustomerDuplicates,
            $this->updaterFor10729PrimaryLocation,
            $this->updaterFor13535SeoTitle,
            $this->updaterFor7995PendingFulfillments
        ];

        foreach ($updaters as $updater) {
            if (!in_array($updater->getName(), $completedUpdates)) {
                $this->runUpdate($updater);
            }
        }
    }

    private function runUpdate(Updater $updater)
    {
        $updater->run();
        $newUpdateModel = new UpdateModel;
        $newUpdateModel->name = $updater->getName();
        $newUpdateModel->save();
        $this->info('Successfully ran update ' . $updater->getName());
    }
}
