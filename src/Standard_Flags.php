<?php

declare(strict_types=1);

/**
 * Builds the always-on flags blob emitted to the browser for every mounted Elm app.
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

class Standard_Flags {

	/**
	 * The script / app handle.
	 *
	 * @var string
	 */
	protected $handle;

	/**
	 * The DOM id the Elm app will mount into.
	 *
	 * @var string
	 */
	protected $mount_node;

	public function __construct( string $handle, string $mount_node ) {
		$this->handle     = $handle;
		$this->mount_node = $mount_node;
	}

	/**
	 * Builds the always-on flags array.
	 *
	 * Merged with plugin-supplied flags inside Elm_App before being handed to wp_localize_script.
	 *
	 * @return array<string, mixed>
	 */
	public function build(): array {
		return array(
			'restRoot'      => \esc_url_raw( \rest_url() ),
			'restNonce'     => \wp_create_nonce( 'wp_rest' ),
			'restNamespace' => 'wp/v2',
			'ajaxUrl'       => \esc_url_raw( \admin_url( 'admin-ajax.php' ) ),
			'ajaxNonce'     => \wp_create_nonce( $this->ajax_action() ),
			'mountNode'     => $this->mount_node,
			'locale'        => \get_locale(),
			'currentUser'   => $this->current_user(),
		);
	}

	/**
	 * The per-handle ajax nonce action.
	 */
	public function ajax_action(): string {
		return 'elm_mount_' . $this->handle;
	}

	/**
	 * Returns a serialisable snapshot of the current user, or null when logged out.
	 *
	 * Capabilities are emitted as a UI hint only — never rely on them for authorisation,
	 * that must happen server side via permission callbacks.
	 *
	 * @return array<string, mixed>|null
	 */
	protected function current_user(): ?array {
		$user = \wp_get_current_user();
		if ( 0 === $user->ID ) {
			return null;
		}

		return array(
			'id'           => (int) $user->ID,
			'displayName'  => (string) $user->display_name,
			'roles'        => array_values( (array) $user->roles ),
			'capabilities' => array_keys( array_filter( $user->allcaps ) ),
		);
	}
}
