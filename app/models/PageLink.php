
<?php

use Illuminate\Database\Eloquent\Model;

class PageLink extends Model
{
    protected $table = 'page_links';
    protected $fillable = [
        'title', 'url', 'description', 'order'
    ];
}
