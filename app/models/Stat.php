<?php
namespace App\Models;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model as Eloquent;
class Stat extends Eloquent {
	protected $table = 'statistics';
	protected $guarded = ['id'];
	public $timestamps = false;

	protected $fillable = [
		'id',
		'project_id',
		'name',
		'display_name',
		'value'
	];

	/**
	 * Get the associated project
	 * @return [type] [description]
	 */
	public function project()
	{
		return $this->belongsTo(Project::class);
	}
}