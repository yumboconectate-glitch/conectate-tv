<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelCategory;
use Illuminate\Console\Command;

class V05Backfill extends Command
{
    protected $signature = 'conectate:v05-backfill';
    protected $description = 'Inicializa catálogo de canales para Conectate TV v0.5';

    public function handle(): int
    {
        $general = ChannelCategory::firstOrCreate(
            ['name' => 'General'],
            ['sort_order' => 100, 'active' => true]
        );

        $number = 1;

        foreach (Channel::orderBy('id')->get() as $channel) {
            $updates = [];

            if (!$channel->display_name) {
                $updates['display_name'] = $channel->name;
            }

            if (!$channel->channel_number) {
                $updates['channel_number'] = $number;
            }

            if (!$channel->category_id) {
                $updates['category_id'] = $general->id;
            }

            if ($updates !== []) {
                $channel->update($updates);
            }

            $number++;
        }

        $this->info('Catálogo v0.5 inicializado: '.Channel::count().' canales.');

        return self::SUCCESS;
    }
}
