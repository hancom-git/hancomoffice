<?php
/**
 *
 * (c) Copyright Hancom Inc Inc
 *
 */

namespace OCA\HancomOffice\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\Template\PublicTemplateResponse;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

use OCA\Files\Helper;

use OCA\HancomOffice\AppConfig;

/**
 * Controller with the main functions
 */
class EditorController extends Controller {

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
     * @param IL10N $trans - l10n service
     * @param ILogger $logger - logger
     * @param AppConfig $config - application configuration
     */
    public function __construct($AppName,
        IRequest $request,
        IRootFolder $root,
        IUserSession $userSession,
        IURLGenerator $urlGenerator,
        IL10N $trans,
        ILogger $logger,
        AppConfig $config
    ) {
        parent::__construct($AppName, $request);

        $this->userSession = $userSession;
        $this->root = $root;
        $this->urlGenerator = $urlGenerator;
        $this->trans = $trans;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Get template file to create
     * 
     * @param string $name - filename
     * 
     * @return string
     */
    private function getTemplate(string $name) {
        $ext = strtolower("." . pathinfo($name, PATHINFO_EXTENSION));
        $templatePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "new" . $ext;
        $template = file_get_contents($templatePath);

        return $template;
    }

    /**
     * Create new file in folder
     *
     * @param string $name - file name
     * @param string $dir - folder path
     *
     * @return array
     *
     * @NoAdminRequired
     * @PublicPage
     */
    public function create($name, $dir) {
        $this->logger->debug("Create: $name", ["app" => $this->appName]);

        if (!$this->config->isUserAllowedToUse()) {
            return ["error" => $this->trans->t("Not permitted")];
        }

        $userId = $this->userSession->getUser()->getUID();
        $userFolder = $this->root->getUserFolder($userId);

        $folder = $userFolder->get($dir);

        if ($folder === null) {
            $this->logger->error("Folder not found: $dir", ["app" => $this->appName]);
            return ["error" => $this->trans->t("Folder not found")];
        }
        if (!$folder->isCreatable()) {
            $this->logger->error("Folder without permission: $dir", ["app" => $this->appName]);
            return ["error" => $this->trans->t("Insufficient permissions")];
        }

        $template = $this->getTemplate($name);
        $name = $folder->getNonExistingName($name);

        try {
            if (\version_compare(\implode(".", \OCP\Util::getVersion()), "19", "<")) {
                $file = $folder->newFile($name);
                $file->putContent($template);
            } else {
                $file = $folder->newFile($name, $template);
            }
        } catch (NotPermittedException $e) {
            $this->logger->logException($e, ["message" => "Can't create file: $name", "app" => $this->appName]);
            return ["error" => $this->trans->t("Can't create file")];
        }

        $fileInfo = $file->getFileInfo();

        $result = Helper::formatFileInfo($fileInfo);
        return $result;
    }

    /**
     * Editor page
     *
     * @param integer $fileId - file id
     * @param string $filePath - file path
     *
     * @return TemplateResponse|RedirectResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index($fileId, $filePath = null, $shareToken = null, $inframe = false) {
        $this->logger->debug("Open: $fileId $filePath $shareToken", ["app" => $this->appName]);

        $isLoggedIn = $this->userSession->isLoggedIn();
        if (!$isLoggedIn) {
            $redirectUrl = $this->urlGenerator->linkToRoute("core.login.showLoginForm", [
                "redirect_url" => $this->request->getRequestUri()
            ]);
            return new RedirectResponse($redirectUrl);
        }

        $documentServerUrl = $this->config->GetHost();

        if (empty($documentServerUrl)) {
            $this->logger->error("documentServerUrl is empty", ["app" => $this->appName]);
            return $this->renderError($this->trans->t("Hancom Office Online app is not configured. Please contact admin"));
        }

        $instanceId = $this->config->getSystemValue('instanceid');
        $userId = $this->userSession->getUser()->getUid();
        // $localecode = $this->config->getUserValue($userId, 'core', 'locale', $this->trans->getLocaleCode());
        $langcode = $this->config->getUserValue($userId, 'core', 'lang', $this->trans->getLanguageCode());
        $params = [
            "lang" => $langcode,
            "fileId" => $instanceId . '_' . $fileId,
            "filePath" => $filePath,
            "userId" => $userId,
            "share-token" => $shareToken
        ];

        $response = new TemplateResponse($this->appName, "editor", $params, $inframe ? "base" : "user");

        $csp = new ContentSecurityPolicy();
        $csp->allowInlineScript(true);

        if (preg_match("/^https?:\/\//i", $documentServerUrl)) {
            $csp->addAllowedScriptDomain($documentServerUrl);
            $csp->addAllowedFrameDomain($documentServerUrl);
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
