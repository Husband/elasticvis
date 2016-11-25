<?php
/**
 * Created by PhpStorm.
 * User: serg
 * Date: 22.11.16
 * Time: 16:02
 */

namespace Matchish\ElasticVis\Models;


use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    protected $table = 'search_query';

    protected $fillable = ['text'];

    public $timestamps = false;

}