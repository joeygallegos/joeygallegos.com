<?php

use Illuminate\Database\Eloquent\Model as Eloquent;
class ProjectFeature extends Eloquent {
	protected $table = 'projectfeature';
	protected $fillable = ['id', 'project_id', 'title', 'description', 'sequence'];
	protected $guarded = ['id'];
	public $timestamps = false;
}