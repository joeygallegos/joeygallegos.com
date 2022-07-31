<?php
use Illuminate\Database\Eloquent\Model as Eloquent;
class ThrownException extends Eloquent {
	protected $table = 'exceptions';
	protected $fillable = [
		'id',
		'message',
		'uri'
	];

	protected $guarded = ['id'];
	public $timestamps = true;
}