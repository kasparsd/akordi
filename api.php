<?php

class Element {
	protected string $value;

	public function __construct( string $value ) {
		$this->value = $value;
	}

	public function get(): string {
		return $this->value;
	}

	private function escape( int $flags ): string {
		return htmlspecialchars( $this->value, $flags, 'UTF-8' );
	}

	public function attr(): string {
		return $this->escape( ENT_QUOTES | ENT_HTML401 );
	}

	public function content(): string {
		return $this->escape( ENT_QUOTES | ENT_SUBSTITUTE );
	}
}

class Akordi {

	private const API_URL = 'https://akordi.lv/api/v2';

	private function log( $message, ...$params ): void {
		printf( $message . PHP_EOL, ...$params );
	}

	private function http_get( $url ): ?string {
		$this->log( 'Fetching %s', $url );

		return file_get_contents( $url );
	}

	public function api_get( string $path, ?array $args = [] ) {
		$url = self::API_URL . '/' . ltrim( $path, '/\\' );

		if ( ! empty( $args ) ) {
			$url .= '?' . http_build_query( $args );
		}

		$response = $this->http_get( $url );

		if ( $response ) {
			return json_decode( $response, true );
		}

		return null;
	}

	public function get_api_items( string $path ): array {
		$all = [];
		$page = 0;

		while ( $items = $this->api_get( $path, [ 'size' => 1000, 'page' => $page ] ) ) {
			if ( ! empty( $items['content'] ) ) {
				$all = array_merge( $all, $items['content'] );
			} else {
				break;
			}

			$page++;
		}

		return $all;
	}

	public function get_song( int $id ): ?array {
		return $this->api_get( 'songs/' . $id );
	}

	public function fetch_songs() {
		foreach ( $this->get_api_items( 'songs' ) as $song ) {
			$song_json_file = sprintf( __DIR__ . '/data/song-%d-details.json' , $song['id'] );

			if ( ! file_exists( $song_json_file ) ) {
				$this->log( 'Fetching song ID %d (%s)', $song['id'], $song['title'] );
				file_put_contents( $song_json_file, json_encode( $this->get_song( $song['id'] ) ) );
			} else {
				$this->log( 'Song %d already fetched', $song['id'] );
			}
		}
	}

	public function template_data() {
		$author_types = [ 'performer', 'composer', 'poet' ];

		$data = [
			'authors' => [],
		];

		foreach ( glob( __DIR__ . '/data/song-*-details.json' ) as $song_file ) {
			$song = json_decode( file_get_contents( $song_file ), true );

			$authors = [ $song['mainArtist'] ];

			$song_data = [
				'slug' => 'dziesma-' . $song['id'],
				'title' => $song['title'],
				'chords' => $song['body'],
				'authors' => array_map(
					function( $author ) {
						return [
							'slug' => 'izpilditajs-' . $author['id'],
							'name' => $author['title'],
						];
					},
					$authors
				),
			];

			foreach ( $authors as $author ) {
				if ( empty( $data['authors'][ $author['id'] ] ) ) {
					$data['authors'][ $author['id'] ] = [
						'slug' => 'izpilditajs-' . $author['id'],
						'name' => $author['title'],
						'songs' => [],
					];
				}

				$data['authors'][ $author['id'] ]['songs'][] = $song_data;
			}
		}

		// Sort authors alphabetically.
		usort(
			$data['authors'],
			function( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);

		// Sort songs alphabetically.
		$data['authors'] = array_map(
			function( $author ) {
				usort(
					$author['songs'],
					function( $a, $b ) {
						return strcmp( $a['title'], $b['title'] );
					}
				);

				return $author;
			},
			$data['authors']
		);

		return $data;
	}
}