<?php

namespace Adshares\CmsBundle\Command;

use Adshares\CmsBundle\Repository\UserRepository;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cms:user:create',
    description: 'Creates a new user',
)]
class CreateUserCommand extends Command
{
    public function __construct(private readonly UserRepository $userRepository, string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'User email address')
            ->addArgument('password', InputArgument::OPTIONAL, 'User password')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Add ADMIN role')
            ->addOption('editor', null, InputOption::VALUE_NONE, 'Add EDITOR role');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email') ?? $io->ask('Email address');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }
        $user = $this->userRepository->findByEmail($email);

        $password = null === $user ?
            str_replace(['+', '/', '='], ['x', 'y', ''], base64_encode(random_bytes(8))) :
            null;
        $password = $input->getArgument('password') ?? $io->ask('Password', $password);
        if (null === $user && empty($password)) {
            throw new RuntimeException('Password cannot be empty.');
        }

        $role = 'USER';
        if ($input->getOption('admin')) {
            $role = 'ADMIN';
        } elseif ($input->getOption('editor')) {
            $role = 'EDITOR';
        }

        if (null === ($user = $this->userRepository->findByEmail($email))) {
            $user = $this->userRepository->createUser($email, $password, ['ROLE_' . $role]);
        } else {
            if (!empty($password)) {
                $user->setPassword($password);
            }
            $user->setRoles(['ROLE_' . $role]);
            $this->userRepository->add($user, true);
        }

        $io->success([
            sprintf('%s %s has been created', $role, $user->getEmail()),
            sprintf('Password: %s', $password),
        ]);

        return Command::SUCCESS;
    }
}
