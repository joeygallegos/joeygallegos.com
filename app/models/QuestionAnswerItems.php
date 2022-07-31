<?php
namespace App\Models;
use App\Models\Stat;
use App\Models\ScopeItem;
use Illuminate\Database\Eloquent\Model as Eloquent;
class QuestionAnswerItems extends Eloquent {
	protected $table = 'qaitems';
	protected $fillable = [
		'id',
		'active',
		'question',
		'answer'
	];
}