<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model as Eloquent;

class ProcessStep extends Eloquent {
	protected $table = 'processsteps';
	protected $fillable = [
		'id',
		'header',
		'description',
		'active',
		'sequence'
	];
	protected $guarded = ['id'];
	public $timestamps = false;
}