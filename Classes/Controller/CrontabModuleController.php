<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Controller;

use Helhum\TYPO3\Crontab\Crontab;
use Helhum\TYPO3\Crontab\Process\ProcessManager;
use Helhum\TYPO3\Crontab\Repository\TaskRepository;
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

    public function __construct(TaskRepository $taskRepository, Crontab $crontab, ProcessManager $processManager = null)
    {
        $this->taskRepository = $taskRepository;
        $this->crontab = $crontab;
        $this->processManager = $processManager ?? GeneralUtility::makeInstance(ProcessManager::class, 1);
        parent::__construct();
    }

    public function listAction(): string
    {
        $this->view->assignMultiple([
            'groupedTasks' => $this->taskRepository->getGroupedTasks(),
            'crontab' => $this->crontab,
            'processManager' => $this->processManager,
            'shortcutLabel' => 'crontab',
            'now' => new \DateTimeImmutable(),
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
}
