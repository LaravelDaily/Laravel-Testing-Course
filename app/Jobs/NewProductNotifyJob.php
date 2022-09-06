<?php

namespace App\Jobs;

use App\Mail\NewProductCreated;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewProductCreatedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NewProductNotifyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Product $product)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $admin = User::where('is_admin', 1)->first();
        Mail::to($admin->email)->send(new NewProductCreated($this->product));

        Notification::send($admin, new NewProductCreatedNotification($this->product));
    }
}
