<?php

/**
 * @package Tests
 * @author Geoffroy AUBRY <geoffroy.aubry@hi-media.com>
 */
class TwgitHotfixTest extends TwgitTestCase
{

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
        $o = self::_getShellInstance();
        $o->remove(TWGIT_REPOSITORY_ORIGIN_DIR);
        $o->remove(TWGIT_REPOSITORY_LOCAL_DIR);
        $o->remove(TWGIT_REPOSITORY_SECOND_REMOTE_DIR);
        $o->mkdir(TWGIT_REPOSITORY_ORIGIN_DIR, '0777');
        $o->mkdir(TWGIT_REPOSITORY_LOCAL_DIR, '0777');
        $o->mkdir(TWGIT_REPOSITORY_SECOND_REMOTE_DIR, '0777');
    }

    /**
     */
    public function testStart_WithAmbiguousRef ()
    {
        $this->_remoteExec('git init');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('git branch v1.2.3 v1.2.3');

        $sMsg = $this->_localExec(TWGIT_EXEC . ' hotfix start -I');
        $this->assertNotContains("warning: refname 'v1.2.3' is ambiguous.", $sMsg);
        $this->assertNotContains("fatal: Ambiguous object name: 'v1.2.3'.", $sMsg);
    }

    /**
     * @dataProvider providerTestListAboutBranchesOutOfProcess
     */
    public function testList_AboutBranchesOutOfProcess ($sLocalCmd, $sExpectedContent, $sNotExpectedContent)
    {
        $this->_remoteExec('git init && git commit --allow-empty -m "-" && git checkout -b feature-currentOfNonBareRepo');
        $this->_localExec(TWGIT_EXEC . ' init 1.2.3 ' . TWGIT_REPOSITORY_ORIGIN_DIR);
        $this->_localExec('cd ' . TWGIT_REPOSITORY_SECOND_REMOTE_DIR . ' && git init');
        $this->_localExec('git remote add second ' . TWGIT_REPOSITORY_SECOND_REMOTE_DIR);

        $this->_localExec($sLocalCmd);
        $sMsg = $this->_localExec(TWGIT_EXEC . ' hotfix list');
        if ( ! empty($sExpectedContent)) {
            $this->assertContains($sExpectedContent, $sMsg);
        }
        if ( ! empty($sNotExpectedContent)) {
            $this->assertNotContains($sNotExpectedContent, $sMsg);
        }
    }

    public function providerTestListAboutBranchesOutOfProcess ()
    {
        return array(
            array(':', '', 'Following branches are out of process'),
            array(':', '', 'Following local branches are ambiguous'),
            array(
                'git checkout -b feature-X && git push origin feature-X'
                    . ' && git checkout -b release-X && git push origin release-X'
                    . ' && git checkout -b hotfix-X && git push origin hotfix-X'
                    . ' && git checkout -b demo-X && git push origin demo-X'
                    . ' && git checkout -b master && git push origin master'
                    . ' && git checkout -b outofprocess && git push origin outofprocess'
                    . ' && git remote set-head origin stable',
                "/!\ Following branches are out of process: 'origin/outofprocess'!",
                'Following local branches are ambiguous'
            ),
            array(
                'git checkout -b outofprocess && git push origin outofprocess && git push second outofprocess'
                    . ' && git checkout -b out2 && git push origin out2 && git push second out2',
                "/!\ Following branches are out of process: 'origin/out2', 'origin/outofprocess'!",
                'Following local branches are ambiguous'
            ),
            array(
                'git branch v1.2.3 v1.2.3',
                "/!\ Following local branches are ambiguous: 'v1.2.3'!",
                'Following branches are out of process'
            ),
            array(
                'git checkout -b outofprocess && git push origin outofprocess && git branch v1.2.3 v1.2.3',
                "/!\ Following branches are out of process: 'origin/outofprocess'!\n"
                    . "/!\ Following local branches are ambiguous: 'v1.2.3'!",
                ''
            ),
        );
    }

}
