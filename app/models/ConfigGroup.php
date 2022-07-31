<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;
class ConfigGroup extends Eloquent {
	use SoftDeletes;

	protected $table = 'config_group';
	protected $guarded = ['id'];
	public $timestamps = true;

	protected $fillable = [
		'id',
		'uuid',
		'group_name',
		'group_description',
		'group_sequence_int',

		'created_at',
		'updated_at',
		'deleted_at'
	];

	public function configEntries()
	{
		return $this->hasMany('App\Models\Config');
	}
}