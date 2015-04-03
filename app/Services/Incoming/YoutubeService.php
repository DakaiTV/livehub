<?php
namespace t2t2\LiveHub\Services\Incoming;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;
use Illuminate\Support\Collection;
use React\Promise\ExtendedPromiseInterface;
use t2t2\LiveHub\Models\Channel;
use t2t2\LiveHub\Models\Stream;

class YoutubeService extends Service {

	/**
	 * Nice name for the user
	 *
	 * @return string
	 */
	public function name() {
		return 'Youtube Live';
	}

	/**
	 * Description of the service to show to user
	 *
	 * @return string
	 */
	public function description() {
		return 'Checking for youtube livestreams';
	}

	/**
	 * Get video URL for this service
	 *
	 * @param null|Channel $channel
	 * @param null|Stream  $stream
	 *
	 * @return string
	 */
	public function getVideoUrl($channel = null, $stream = null) {
		if ($stream->service_info) {
			return 'http://www.youtube.com/embed/' . $stream->service_info . '?autohide=1&autoplay=1';
		}

		return parent::getVideoUrl($channel, $stream);
	}

	/**
	 * Configuration setting available for this service
	 *
	 * @return array
	 */
	public function serviceConfig() {
		return [
			['name' => 'api_key', 'type' => 'text', 'label' => 'Youtube API Key', 'rules' => ['required']],
		];
	}

	public function channelConfig() {
		return [
			['name' => 'channel_id', 'type' => 'text', 'label' => 'Channel ID', 'rules' => ['required']],
		];
	}

	/**
	 * Is the service configured to be checkable
	 *
	 * @return bool
	 */
	public function isCheckable() {
		return isset($this->getOptions()->api_key) && strlen($this->getOptions()->api_key) > 0;
	}

	/**
	 * Check channel for live streams
	 *
	 * @param Channel $channel
	 *
	 * @return ExtendedPromiseInterface
	 */
	public function check(Channel $channel) {
		$channel_id = $channel->options->channel_id;

		$client = new Client([
			'base_url' => 'https://www.googleapis.com/youtube/v3/',
			'defaults' => [
				'query'  => [
					'key' => $this->getOptions()->api_key,
				],
				'verify' => storage_path('cacert.pem'),
			],
		]);

		$promise = \React\Promise\all(
			array_map($this->requestLiveOfTypeCallback($client, $channel_id),
				['upcoming', 'live'])
		);
		$promise = $this->findVideoIDsFromRequest($promise);
		$promise = $this->requestDataForVideoIDs($promise, $client);
		$promise = $this->tranformVideoDataToLocal($promise);
		$promise = $this->reformatServiceErrors($promise);

		return $promise;
	}

	/**
	 * Returns a callback that finds live videos from channel depending on livestatus
	 *
	 * @param Client $client
	 * @param string $channel_id
	 *
	 * @return callable
	 */
	protected function requestLiveOfTypeCallback(Client $client, $channel_id) {
		return function ($type) use ($client, $channel_id) {
			// Get live videos that are upcoming or live
			return $client->get('search', [
				'query'  => [
					'part'      => 'snippet',
					'channelId' => $channel_id,
					'type'      => 'video',
					'eventType' => $type,
				],
				'future' => true
			]);
		};
	}

	/**
	 * Finds video IDs from youtube search request
	 *
	 * @param $promise
	 *
	 * @return ExtendedPromiseInterface
	 */
	protected function findVideoIDsFromRequest(ExtendedPromiseInterface $promise) {
		return $promise->then(function ($responses) {
			// Find the video IDs
			$ids = [];
			/** @var Response[] $responses */
			foreach ($responses as $response) {
				$results = $response->json();
				foreach ($results['items'] as $item) {
					$ids[] = $item['id']['videoId'];
				}
			}

			// Duplicates can happen between different requests (ty caching)
			$ids = array_unique($ids);

			return $ids;
		});
	}

	/**
	 * Gets data from youtube API about the list of video IDs
	 *
	 * @param ExtendedPromiseInterface $promise
	 * @param Client                   $client
	 *
	 * @return ExtendedPromiseInterface
	 */
	protected function requestDataForVideoIDs(ExtendedPromiseInterface $promise, Client $client) {
		return $promise->then(function ($ids) use ($client) {
			// Get data for all of the found videos
			if (count($ids) == 0) {
				return new Collection();
			}

			return $client->get('videos', [
				'query'  => [
					'part' => 'snippet,liveStreamingDetails',
					'id'   => implode(',', $ids),
				],
				'future' => true
			]);
		});
	}

	/**
	 * Converts data from videos list to data livehub can use
	 *
	 * @param ExtendedPromiseInterface $promise
	 *
	 * @return ExtendedPromiseInterface
	 */
	protected function tranformVideoDataToLocal(ExtendedPromiseInterface $promise) {
		return $promise->then(function ($response) {
			// Skip if no videos
			if ($response instanceof Collection) {
				return $response;
			}

			// Format data from the videos to universal updater
			/** @var Response $response */
			$results = $response->json();

			$videos = array_map(function ($item) {
				/* Youtube bug: Search results may return cached response where live videos are listed
				even way after they're over. This does not happen in /videos (here) */
				if (! $item['snippet']['liveBroadcastContent'] || $item['snippet']['liveBroadcastContent'] == 'none') {
					return null;
				}

				$info = new ShowData();
				$info->service_info = $item['id'];
				$info->title = $item['snippet']['title'];
				$info->state = $item['snippet']['liveBroadcastContent'] == 'upcoming' ? 'next' : 'live';
				if (isset($item['liveStreamingDetails']['actualStartTime'])) {
					$info->start_time = Carbon::parse($item['liveStreamingDetails']['actualStartTime']);
				} elseif (isset($item['liveStreamingDetails']['scheduledStartTime'])) {
					$info->start_time = Carbon::parse($item['liveStreamingDetails']['scheduledStartTime']);
				}

				return $info;
			}, $results['items']);

			$videos = array_filter($videos);

			return Collection::make($videos);
		});
	}

	/**
	 * Reformat any service errors that may have happened
	 *
	 * @param ExtendedPromiseInterface $promise
	 *
	 * @return ExtendedPromiseInterface
	 */
	protected function reformatServiceErrors(ExtendedPromiseInterface $promise) {
		return $promise->otherwise(function (RequestException $e) {
			// If request error happens anywhere, try to find the error message and use that
			if ($e->hasResponse()) {
				$response = $e->getResponse()->json();
				if (isset($response['error']['errors'][0]['message'])) {
					throw new Exception($response['error']['errors'][0]['message'], $e->getCode(), $e);
				}
			}
			throw $e;
		});
	}

}