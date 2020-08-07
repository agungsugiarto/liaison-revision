<?php

namespace Liaison\Revision\Tests\Commands;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Filters\CITestStreamFilter;

class GitignoreCommandTest extends CIUnitTestCase
{
    private $streamFilter;
    private $original = ROOTPATH . '.gitignore';
    private $backup   = ROOTPATH . 'backup.gitignore';

    protected function setUp(): void
    {
        parent::setUp();

        CITestStreamFilter::$buffer = '';
        $this->streamFilter         = stream_filter_append(STDOUT, 'CITestStreamFilter');
        $this->streamFilter         = stream_filter_append(STDERR, 'CITestStreamFilter');

        if (is_file($this->original)) {
            copy($this->original, $this->backup);
        }
    }

    protected function tearDown(): void
    {
        stream_filter_remove($this->streamFilter);

        if (is_file($this->original)) {
            unlink($this->original);
        }

        if (is_file($this->backup)) {
            rename($this->backup, $this->original);
        }
    }

    public function testBothOptionsProvidedThrowsExceptions()
    {
        try {
            command('revision:gitignore -allow-entry -no-allow-entry');
        } catch (\Throwable $e) {
            ob_end_clean();
            $this->assertInstanceOf('Liaison\Revision\Exception\LogicException', $e);
        }
    }

    public function testDeniedWriteToGitignore()
    {
        command('revision:gitignore -no-allow-entry');
        $this->assertStringContainsString('not allowed to write', CITestStreamFilter::$buffer);
    }

    public function testWriteNewGitignoreIfMissing()
    {
        unlink($this->original);
        command('revision:gitignore -write-if-missing');
        $this->assertStringContainsString('Successfully created', CITestStreamFilter::$buffer);
    }

    public function testCommandWarnsOnAlreadyWrittenGitignore()
    {
        command('revision:gitignore');
        CITestStreamFilter::$buffer = '';

        command('revision:gitignore');
        $this->assertStringContainsString('There was already an entry', CITestStreamFilter::$buffer);
    }

    public function testWriteFailsOnUnwritableFile()
    {
        chmod($this->original, 0444);
        command('revision:gitignore');
        $this->assertStringContainsString('Failed creating entry', CITestStreamFilter::$buffer);
        chmod($this->original, 0644);
    }
}