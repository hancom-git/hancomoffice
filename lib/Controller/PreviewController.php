<?php
/**
 *
 * (c) Copyright Hancom Inc
 *
 */

namespace OCA\HancomOffice\Controller;

use OCP\App;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Share\IManager;

use OCA\HancomOffice\AppConfig;
use OCA\HancomOffice\Service\Preview\PreviewService;

/**
 * Settings controller for the administration page
 */
class PreviewController extends Controller {

    /**
     * Current user session
     *
     * @var IUserSession
     */
    private $userSession;

    /**
     * Root folder
     *
     * @var IRootFolder
     */
    private $root;

    /**
     * Url generator service
     *
     * @var IURLGenerator
     */
    private $urlGenerator;

    /** @var IManager */
	private $shareManager;

    /**
     * l10n service
     *
     * @var IL10N
     */
    private $trans;

    /**
     * Logger
     *
     * @var ILogger
     */
    private $logger;

    /**
     * Application configuration
     *
     * @var AppConfig
     */
    private $config;

    /**
     * @param string $AppName - application name
     * @param IRequest $request - request object
     * @param IRootFolder $root - root folder
     * @param IUserSession $userSession - current user session
     * @param IURLGenerator $urlGenerator - url generator service
     * @param IManager $shareManager
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param AppConfig $config - application configuration
     */
    public function __construct($AppName,
        IRequest $request,
        IRootFolder $root,
        IUserSession $userSession,
        IURLGenerator $urlGenerator,
        IManager $shareManager,
        IL10N $trans,
        ILogger $logger,
        AppConfig $config,
        PreviewService $previewService
    ) {
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->shareManager = $shareManager;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
        $this->previewService = $previewService;
    }

    /**
     * File preview route
     *
     * @return string
     * 
     * @PublicPage
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($fileId, $filePath = null, $inframe = false, $shareToken = null) {
        $this->logger->debug("Preview: $fileId $filePath", ["app" => $this->appName]);

        $isLoggedIn = $this->userSession->isLoggedIn();
        if ($isLoggedIn) {
            $userId = $this->userSession->getUser()->getUid();
        }
        if (!$isLoggedIn && !$shareToken) {
            $redirectUrl = $this->urlGenerator->linkToRoute("core.login.showLoginForm", [
                "redirect_url" => $this->request->getRequestUri()
            ]);
            return new RedirectResponse($redirectUrl);
        }

        $docsconverterUrl = $this->config->GetDocsConverterUrl();

        if (empty($docsconverterUrl)) {
            $this->logger->error("docsconverterUrl is empty", ["app" => $this->appName]);
            return $this->renderError($this->trans->t("DocsConverter app is not configured. Please contact admin"));
        }

        if ($shareToken) {
            $share = $this->shareManager->getShareByToken($shareToken);
            if ($share) {
                $userId = $share->getShareOwner();
            }
        }

        $hviewUrl = $this->previewService->getHViewUrl($fileId, $userId, $filePath);

        if (!$hviewUrl) {
            return $this->renderError($this->trans->t("Internal Error"));
        }

        $templateParams = [
            "url" => $hviewUrl
        ];

        $response = new TemplateResponse($this->appName, "preview", $templateParams, $inframe ? "base" : "user");

        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);

        if (preg_match("/^https?:\/\//i", $docsconverterUrl)) {
            $csp->addAllowedScriptDomain($docsconverterUrl);
            $csp->addAllowedConnectDomain($docsconverterUrl);
            $csp->addAllowedFrameDomain($docsconverterUrl);
        } else {
            $csp->addAllowedFrameDomain("'self'");
        }
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    /**
     * Error page
     *
     * @param string $error - error message
     * @param string $hint - error hint
     *
     * @return TemplateResponse
     */
    private function renderError($error) {
        return new TemplateResponse(
            "",
            "error",
            [
                "errors" => [["error" => $error]]
            ],
            "error"
        );
    }
}
