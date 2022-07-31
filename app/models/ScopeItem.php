<?php
namespace App\Models;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model as Eloquent;
class ScopeItem extends Eloquent {
	protected $table = 'scopeitem';
	protected $fillable = [
		'id',
		'active',
		'project_id',
		'description'
	];
	protected $guarded = ['id'];
	public $timestamps = false;

	public function project() {
		return $this->belongsTo(Project::class);
	}
}