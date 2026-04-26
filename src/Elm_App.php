<?php

declare(strict_types=1);

/**
 * Mount a compiled Elm bundle into a WordPress page with a standard flags blob.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author Glynn Quelch <glynn.quelch@gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @package PinkCrab\ElmMount
 */

namespace PinkCrab\ElmMount;

class Elm_App {

	/**
	 * Script / app handle. Also used as the JS global name for the flags blob.
	 *
	 * @var string
	 */
	protected $handle;

	/**
	 * URI of the compiled Elm bundle.
	 *
	 * @var string
	 */
	protected $script_src = '';

	/**
	 * Script dependencies.
	 *
	 * @var array<int, string>
	 */
	protected $script_deps = array();

	/**
	 * Script version. Null = WP default. False = no version string.
	 *
	 * @var string|bool|null
	 */
	protected $script_ver = false;

	/**
	 * Whether the script should load in the footer.
	 *
	 * @var bool
	 */
	protected $script_in_footer = true;

	/**
	 * Plugin-supplied flags merged into `pluginData` on the flags blob.
	 *
	 * @var array<string, mixed>
	 */
	protected $plugin_flags = array();

	/**
	 * Overrides the default mount node id of `{handle}-root`.
	 *
	 * @var string|null
	 */
	protected $mount_node_id = null;

	/**
	 * Whether enqueue + localize has already run for this instance.
	 *
	 * @var bool
	 */
	protected $enqueued = false;

	public function __construct( string $handle ) {
		$this->handle = $handle;
	}

	/**
	 * Fluent constructor.
	 */
	public static function create( string $handle ): self {
		return new self( $handle );
	}

	/**
	 * Sets the compiled-bundle URI and wp_enqueue_script options.
	 *
	 * @param string           $src       URI of the compiled Elm bundle.
	 * @param array<int,string> $deps     Script handles this depends on.
	 * @param string|bool|null $ver       Version string; false = none; null = WP default.
	 * @param bool             $in_footer Load in the footer. Defaults to true.
	 */
	public function script( string $src, array $deps = array(), $ver = false, bool $in_footer = true ): self {
		$this->script_src       = $src;
		$this->script_deps      = $deps;
		$this->script_ver       = $ver;
		$this->script_in_footer = $in_footer;
		return $this;
	}

	/**
	 * Plugin-specific flags. Exposed on the blob at `pluginData`.
	 *
	 * @param array<string, mixed> $flags
	 */
	public function flags( array $flags ): self {
		$this->plugin_flags = $flags;
		return $this;
	}

	/**
	 * Overrides the default mount node id (`{handle}-root`).
	 */
	public function mount_node( string $id ): self {
		$this->mount_node_id = $id;
		return $this;
	}

	/**
	 * Enqueues the bundle, localizes the flags, and echoes the mount div.
	 */
	public function render(): void {
		$this->ensure_enqueued();
		echo $this->mount_div_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Enqueues the bundle, localizes the flags, and returns the mount div HTML.
	 */
	public function parse(): string {
		$this->ensure_enqueued();
		return $this->mount_div_html();
	}

	/**
	 * Idempotent — safe to call more than once per request.
	 */
	protected function ensure_enqueued(): void {
		if ( $this->enqueued ) {
			return;
		}

		\wp_enqueue_script(
			$this->handle,
			$this->script_src,
			$this->script_deps,
			$this->script_ver,
			$this->script_in_footer
		);

		\wp_localize_script( $this->handle, $this->handle, $this->build_flags() );

		$this->enqueued = true;
	}

	/**
	 * Builds the full flags array emitted to the browser.
	 *
	 * @return array<string, mixed>
	 */
	protected function build_flags(): array {
		$standard = ( new Standard_Flags( $this->handle, $this->resolved_mount_node() ) )->build();
		return array_merge( $standard, array( 'pluginData' => $this->plugin_flags ) );
	}

	/**
	 * Mount node id, either user-supplied or the default `{handle}-root`.
	 */
	protected function resolved_mount_node(): string {
		return $this->mount_node_id ?? ( $this->handle . '-root' );
	}

	/**
	 * Escaped mount-div HTML.
	 */
	protected function mount_div_html(): string {
		return '<div id="' . \esc_attr( $this->resolved_mount_node() ) . '"></div>';
	}
}
