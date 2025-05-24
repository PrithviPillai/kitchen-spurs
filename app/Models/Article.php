<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Article
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string $summary
 * @property int $user_id
 * @property string $status
 * @property string|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User $user
 * @property Collection|Category[] $categories
 *
 * @package App\Models
 */
class Article extends Model
{
	use SoftDeletes;
	protected $table = 'articles';

	protected $casts = [
		'user_id' => 'int'
	];

	protected $fillable = [
		'title',
		'slug',
		'content',
		'summary',
		'user_id',
		'status'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function categories()
	{
		return $this->belongsToMany(Category::class, 'article_category', 'article', 'category')
					->withPivot('id')
					->withTimestamps();
	}
}
