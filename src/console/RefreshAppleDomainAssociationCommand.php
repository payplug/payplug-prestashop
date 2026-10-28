<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\console;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\DependenciesClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshAppleDomainAssociationCommand extends Command
{
    public const NAME = 'payplug:apple-pay:refresh-well-known';

    private const ACTION_SERVICE = 'payplug.action.apple_pay_domain_association';

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Download the Apple Pay domain association file from PayPlug again and store it.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->getAction()->refreshAction();

        if (!$result['success']) {
            $output->writeln('Synchronisation failed: ' . $result['error'], OutputInterface::OUTPUT_RAW);

            return 1;
        }

        $output->writeln('Synchronisation done (' . $result['status'] . ').', OutputInterface::OUTPUT_RAW);

        return 0;
    }

    /**
     * @description Resolve the action when the command runs: listing the console commands builds
     * every command, and must not build the module dependencies.
     *
     * @return object
     */
    protected function getAction()
    {
        $dependencies = new DependenciesClass();

        return $dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($dependencies->name)
            ->getService(self::ACTION_SERVICE);
    }
}
