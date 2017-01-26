<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Telegram\Bot\Api;

class publish extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channel:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'publish post to channel';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new Client();
        $crawler = $client->request('GET', 'http://www.aparat.com/tech');

        $counter = 0;
        $lastvideos = cache('lastvideos', []);
        $new_videos = [];

        $telegram = new Api();

        $crawler->filter('.video-item__thumb')->each(function ($node) use ($client, $telegram, &$counter, $lastvideos, &$new_videos) {
            if($counter < 5) {
                $href = $node->attr('href');
                $href_arr = explode('/', $href);
                if(count($href_arr) > 4) {
                    if ($href_arr[3] == 'v') {
                        if (!in_array($href_arr[4], $lastvideos)) {
                            try {
                                $this->info('=====================');

                                $crawler2 = $client->request('GET', $href);
                                $link = $crawler2->filter('.download-link > a')->first();

                                $video = explode('?', $link->attr('href'));
                                $video = $video[0];

                                $this->info('title: ' . $node->attr('title'));
                                $this->info('link: ' . $video);


                                $this->info('publishing message ...');
                                $telegram->sendVideo([
                                    'chat_id' => '@axmaxclip',
                                    'video' => $video,
                                    'caption' => $node->attr('title') . "\n\n📽 @axmaxclip"
                                ]);

                                $this->info('done');

                                $new_videos[] = $href_arr[4];
                                cache(['lastvideos' => $new_videos], Carbon::now()->addYears(1));
                                $counter++;
                            } catch (\Exception $e) {
                                $this->warn($e->getMessage());
                            }
                        }
                    }
                }
            }
        });



    }
}
