<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;
class Testimony extends Eloquent {
	protected $table = 'testimonies';
	protected $fillable = [
		'id',
		'project_id',
		'quote',
		'who',
		'title'
	];
	protected $guarded = ['id'];
	public $timestamps = false;

	public function project() {
		return $this->belongsTo(Project::class);
	}
}