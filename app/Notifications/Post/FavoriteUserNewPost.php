<?php

namespace App\Notifications\Post;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FavoriteUserNewPost extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Post $post)
    {
        $this->post->load('user');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $author = $this->post->user;

        return (new MailMessage)
            ->subject("{$author->name} has created a new post")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$author->name} has just created a new post that you might be interested in.")
            ->line("**{$this->post->title}**")
            ->line($this->post->body)
            ->action('View Post', route('posts.show', $this->post))
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->id,
            'post_title' => $this->post->title,
            'author_id' => $this->post->user->id,
            'author_name' => $this->post->user->name,
        ];
    }
}
