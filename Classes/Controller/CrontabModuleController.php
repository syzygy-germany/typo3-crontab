<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Controller;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\Process\ProcessManager;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class CrontabModuleController extends ActionController
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

    /**
     * @var Crontab
     */
    private $crontab;

    /**
     * @var ProcessManager
     */
    private $processManager;

    public function __construct(?TaskRepository $taskRepository = null, ?Crontab $crontab = null, ProcessManager $processManager = null)
    {
        parent::__construct();
        $this->taskRepository = $taskRepository ?? GeneralUtility::makeInstance(TaskRepository::class);
        $this->crontab = $crontab ?? GeneralUtility::makeInstance(Crontab::class);
        $this->processManager = $processManager ?? GeneralUtility::makeInstance(ProcessManager::class, 1);
    }

    public function listAction(): string
    {
        $this->view->assignMultiple([
            'groupedTasks' => $this->taskRepository->getGroupedTasks(),
            'crontab' => $this->crontab,
            'processManager' => $this->processManager,
            'shortcutLabel' => 'crontab',
            'now' => new \DateTimeImmutable(),
            'validateStatusViaAjax' => (int) $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['crontab']['validateStatusViaAjax']
        ]);

        return $this->view->render();
    }

    /**
     * @param string $identifier
     */
    public function toggleScheduleAction(string $identifier): void
    {
        $taskDefinition = $this->taskRepository->findByIdentifier($identifier);
        if ($this->crontab->isScheduled($taskDefinition)) {
            $this->crontab->removeFromSchedule($taskDefinition);
        } else {
            $this->crontab->schedule($taskDefinition);
        }

        $this->redirect('list');
    }

    /**
     * @param array $identifier
     */
    public function scheduleForImmediateExecutionAction(array $identifiers): void
    {
        foreach ($identifiers as $identifier) {
            $this->crontab->scheduleForImmediateExecution(
                $this->taskRepository->findByIdentifier($identifier)
            );
        }

        $this->redirect('list');
    }

    /**
     * @param string $identifier
     */
    public function terminateAction(string $identifier): void
    {
        $this->processManager->terminateAllProcesses($identifier);
        $this->addFlashMessage(sprintf('Terminated processes for task "%s"', $identifier));

        $this->redirect('list');
    }

    public function hasRunningProcesses(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        if (! isset($parsedBody['identifiers'])) {
            $response->getBody()->write('');
            return $response;
        }

        $hasRunningProcesses = [];
        foreach($parsedBody['identifiers'] as $identifier) {
            $hasRunningProcesses[$identifier] = $this->processManager->hasRunningProcesses($identifier);
        }

        $response->getBody()->write(json_encode($hasRunningProcesses));
        return $response;
    }
}
