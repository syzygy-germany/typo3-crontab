<?php
declare(strict_types=1);
namespace Helhum\TYPO3\Crontab\Command;

use Helhum\TYPO3\Crontab\Repository\TaskRepository;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CrontabProcessCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        if ($this->logger === null || version_compare(TYPO3_branch, '9.0', '<')) {
            $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }
    }

    public function configure(): void
    {
        $this->setDescription('Directly runs the Crontab Task with given identifier.')
            ->setHelp(
                <<<EOH
This will execute the given task even when not scheduled, or it is already running.
Executing it directly will also NOT mark it as a running scheduled task.
EOH
)
            ->addArgument('taskIdentifier', InputArgument::REQUIRED, 'Identifier of task that should be run');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskIdentifier = $input->getArgument('taskIdentifier');
        $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $taskDefinition = $taskRepository->findByIdentifier($taskIdentifier);

        try {
            $success = $taskDefinition->getProcessDefinition()->getExecutor()->run(
                $this->getApplication(),
                $input,
                $output
            );
        } catch (\Throwable $e) {
            $this->logger->error(sprintf('Task "%s" failed with exception.', $taskIdentifier), ['exception' => $e]);
            throw $e;
        }
        if (!$success) {
            $this->logger->error(sprintf('Task "%s" did not complete successfully.', $taskIdentifier));

            return 1;
        }

        return 0;
    }
}
