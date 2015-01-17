<?php
namespace t2t2\LiveHub\Models;

use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

/**
 * t2t2\LiveHub\Models\Stream
 *
 * @property integer                                        $id
 * @property integer                                        $channel_id
 * @property array                                          $service_info
 * @property string                                         $title
 * @property string                                         $state
 * @property \Carbon\Carbon                                 $start_time
 * @property string                                         $video_url
 * @property string                                         $chat_url
 * @property \Carbon\Carbon                                 $created_at
 * @property \Carbon\Carbon                                 $updated_at
 * @property-read \t2t2\LiveHub\Models\Channel              $channel
 * @property-read \Illuminate\Database\Eloquent\Collection|\$related[] $morphedByMany
 * @method static Builder|Stream whereId($value)
 * @method static Builder|Stream whereChannelId($value)
 * @method static Builder|Stream whereServiceInfo($value)
 * @method static Builder|Stream whereTitle($value)
 * @method static Builder|Stream whereState($value)
 * @method static Builder|Stream whereStartTime($value)
 * @method static Builder|Stream whereVideoUrl($value)
 * @method static Builder|Stream whereChatUrl($value)
 * @method static Builder|Stream whereCreatedAt($value)
 * @method static Builder|Stream whereUpdatedAt($value)
 */
class Stream extends Model {

	protected $casts = ['options' => 'json'];

	protected $dates = ['start_time'];

	protected $fillable = ['channel_id', 'service_info', 'title', 'state', 'start_time', 'video_url', 'chat_url'];

	protected $hidden = ['service_info'];

	protected $table = 'streams';

	// Relations

	public function channel() {
		return $this->belongsTo('t2t2\LiveHub\Models\Channel');
	}

	// Setters
	public function setStartTimeAttribute($value) {
		if(! ($value instanceof DateTime)) {
			$value = Carbon::parse($value);
		}
		$this->attributes['start_time'] = $value;
	}

}