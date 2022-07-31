<?php

use Illuminate\Database\Eloquent\Model as Eloquent;
class WorkExperience extends Eloquent {
	protected $table = 'workexperience';
	protected $fillable = ['id', 'name', 'year', 'tooltip', 'active', 'sequence'];
	protected $guarded = ['id'];
	public $timestamps = false;
}