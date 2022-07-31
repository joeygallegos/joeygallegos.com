<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;
class Service extends Eloquent {
	protected $table = 'services';
	protected $guarded = ['id'];
	public $timestamps = false;

	protected $fillable = [
		'id',
		'active',
		'title',
		'description'
	];
}