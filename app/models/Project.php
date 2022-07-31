<?php
namespace App\Models;
use App\Models\Stat;
use App\Models\ScopeItem;
use App\Models\Testimony;
use Illuminate\Database\Eloquent\Model as Eloquent;
class Project extends Eloquent {
	protected $table = 'projects';
	protected $fillable = [
		'id',
		'slug',
		'active',
		'title',
		'short_description',
		'brief_scope',
		'description',
		'tags',
		'link',
		'view_link',
		'image'
	];
	protected $guarded = ['id'];
	public $timestamps = false;	

	/**
	 * Get project stats
	 * @return Stat
	 */
	public function getStats()
	{
		return $this->hasMany(Stat::class);
	}

	/**
	 * Get project scope items
	 * @return ScopeItem
	 */
	public function getScopeItems()
	{
		return $this->hasMany(ScopeItem::class);
	}

	/**
	 * Get project testimony
	 * @return ScopeItem
	 */
	public function getTestimony()
	{
		return $this->hasOne(Testimony::class);
	}

	public function toProjectArray() {
		return [
			'id' => $this->id,
			'slug' => $this->slug,
			'active' => $this->active,
			'title' => $this->title,
			'brief_scope' => $this->brief_scope,
			'description' => $this->description,
			'short_description' => $this->short_description,
			'link' => $this->link,
			'view_link' => $this->view_link,
			'image' => $this->image,
			'stats' => $this->stats
		];
	}

}