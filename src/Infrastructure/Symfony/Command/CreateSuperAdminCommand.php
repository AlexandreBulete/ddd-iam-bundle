<?php

declare(strict_types=1);

namespace AlexandreBulete\DddIamBundle\Infrastructure\Symfony\Command;

use AlexandreBulete\DddFoundation\Application\Command\CommandBusInterface;
use AlexandreBulete\DddIamBundle\Application\Command\CreateUser\CreateUserCommand;
use AlexandreBulete\DddIamBundle\Domain\Exception\PasswordPolicyViolation;
use AlexandreBulete\DddIamBundle\Domain\Exception\UnknownRoleException;
use AlexandreBulete\DddIamBundle\Domain\Model\User;
use AlexandreBulete\DddIamBundle\Domain\Repository\UserRepositoryInterface;
use AlexandreBulete\DddIamBundle\Domain\Service\RoleCatalogInterface;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\Email;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\PlainPassword;
use AlexandreBulete\DddIamBundle\Domain\ValueObject\RoleSet;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Bootstrap CLI — creates the first account able to log into the back office.
 *
 * The chicken-and-egg command: without it a fresh install has an admin UI
 * nobody can enter. Idempotent on email, so it is safe in a provisioning
 * script that runs on every deploy.
 *
 * No credential default ships with the bundle, on purpose. A default password
 * baked into a package is a default password running in production somewhere.
 */
#[AsCommand(
    name: 'iam:create-super-admin',
    description: 'Create the first back-office administrator (idempotent on email).',
)]
final class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleCatalogInterface $roleCatalog,
        private readonly string $superAdminRole,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Administrator email')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password (prompted hidden if omitted)')
            ->addOption('first-name', null, InputOption::VALUE_REQUIRED, 'First name')
            ->addOption('last-name', null, InputOption::VALUE_REQUIRED, 'Last name')
            ->addOption(
                'role',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                sprintf('Roles to grant (default: %s)', $this->superAdminRole),
            )
            ->setHelp(<<<'HELP'
                Creates the first administrator so that someone can log into the back office.

                  <info>php %command.full_name% --email=admin@example.com</info>

                Non-interactive (CI, provisioning):

                  <info>php %command.full_name% --email=admin@example.com --password=… --no-interaction</info>

                Re-running with an email that already exists changes nothing and exits 0.
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $emailInput = $input->getOption('email') ?? $io->ask('Email');
        if (!is_string($emailInput) || trim($emailInput) === '') {
            $io->error('An email is required (--email).');

            return Command::INVALID;
        }

        try {
            $email = new Email($emailInput);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        // Checked before asking for a password: nobody should have to type a
        // secret to be told the account already exists.
        if ($this->userRepository->findOneByEmail($email->value()) !== null) {
            $io->warning(sprintf('User "%s" already exists — nothing to do.', $email->value()));

            return Command::SUCCESS;
        }

        $passwordInput = $input->getOption('password');
        if ($passwordInput === null) {
            $passwordInput = $this->askHiddenPassword($input, $output);
        }

        if (!is_string($passwordInput) || $passwordInput === '') {
            $io->error('A password is required (--password).');

            return Command::INVALID;
        }

        $roleNames = $input->getOption('role');
        $roles = $roleNames === []
            ? RoleSet::fromNames([$this->superAdminRole])
            : RoleSet::fromNames($roleNames);

        try {
            $this->roleCatalog->assertKnown($roles);
        } catch (UnknownRoleException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        try {
            /** @var User $user */
            $user = $this->commandBus->dispatch(new CreateUserCommand(
                email: $email,
                password: new PlainPassword($passwordInput),
                firstName: $input->getOption('first-name'),
                lastName: $input->getOption('last-name'),
                roles: $roles,
            ));
        } catch (PasswordPolicyViolation $e) {
            $io->error('Password policy violated:');
            $io->listing($e->violations);

            return Command::INVALID;
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }

        $io->success(sprintf(
            'Administrator created: %s (id=%s, roles=%s).',
            $user->email->value(),
            (string) $user->id,
            implode(', ', $user->roles->toStrings()),
        ));

        return Command::SUCCESS;
    }

    private function askHiddenPassword(InputInterface $input, OutputInterface $output): string
    {
        $question = new Question('Password (hidden): ');
        $question->setHidden(true);
        // No fallback to a visible prompt: echoing a password to a terminal
        // that may be logged is worse than failing and asking for --password.
        $question->setHiddenFallback(false);

        $helper = $this->getHelper('question');
        \assert($helper instanceof \Symfony\Component\Console\Helper\QuestionHelper);

        $answer = $helper->ask($input, $output, $question);

        return is_string($answer) ? $answer : '';
    }
}
