<?php
/**
 * <h2>Fixture tree</h2>
 * <code>
 * parent1_published
 *   child1_1_published
 *     grandchild1_1_1
 *     grandchild1_1_2_published
 *     grandchild1_1_3_orphaned
 *     grandchild1_1_4_orphaned_published
 *   child1_2_published
 *   child1_3_orphaned
 *   child1_4_orphaned_published
 * parent2
 *   child2_1_published_orphaned // is orphaned because parent is not published
 * </code>
 * 
 * <h2>Cleaned up tree</h2>
 * <code>
 * parent1_published
 *   child1_1_published
 *     grandchild1_1_1
 *     grandchild1_1_2_published
 *   child2_1_published_orphaned
 * parent2
 * </code>
 * 
 * @author Ingo Schommer (<firstname>@silverstripe.com), SilverStripe Ltd.
 * 
 * @package sapphire
 * @subpackage tests
 */
class RemoveOrphanedPagesTaskTest extends FunctionalTest {
	
	static $fixture_file = 'sapphire/tests/tasks/RemoveOrphanedPagesTaskTest.yml';
	
	static $use_draft_site = false;
	
	function setUp() {
		parent::setUp();
		
		$parent1_published = $this->objFromFixture('Page', 'parent1_published');
		$parent1_published->publish('Stage', 'Live');
		
		$child1_1_published = $this->objFromFixture('Page', 'child1_1_published');
		$child1_1_published->publish('Stage', 'Live');
		
		$child1_2_published = $this->objFromFixture('Page', 'child1_2_published');
		$child1_2_published->publish('Stage', 'Live');
		
		$child1_3_orphaned = $this->objFromFixture('Page', 'child1_3_orphaned');
		$child1_3_orphaned->ParentID = 9999;
		$child1_3_orphaned->write();
		
		$child1_4_orphaned_published = $this->objFromFixture('Page', 'child1_4_orphaned_published');
		$child1_4_orphaned_published->ParentID = 9999;
		$child1_4_orphaned_published->write();
		$child1_4_orphaned_published->publish('Stage', 'Live');
		
		$grandchild1_1_2_published = $this->objFromFixture('Page', 'grandchild1_1_2_published');
		$grandchild1_1_2_published->publish('Stage', 'Live');
		
		$grandchild1_1_3_orphaned = $this->objFromFixture('Page', 'grandchild1_1_3_orphaned');
		$grandchild1_1_3_orphaned->ParentID = 9999;
		$grandchild1_1_3_orphaned->write();
		
		$grandchild1_1_4_orphaned_published = $this->objFromFixture('Page',
			'grandchild1_1_4_orphaned_published'
		);
		$grandchild1_1_4_orphaned_published->ParentID = 9999;
		$grandchild1_1_4_orphaned_published->write();
		$grandchild1_1_4_orphaned_published->publish('Stage', 'Live');
		
		$child2_1_published_orphaned = $this->objFromFixture('Page', 'child2_1_published_orphaned');
		$child2_1_published_orphaned->publish('Stage', 'Live');
	}
	
	function testGetOrphansByStage() {
		// all orphans
		$child1_3_orphaned = $this->objFromFixture('Page', 'child1_3_orphaned');
		$child1_4_orphaned_published = $this->objFromFixture('Page', 'child1_4_orphaned_published');
		$grandchild1_1_3_orphaned = $this->objFromFixture('Page', 'grandchild1_1_3_orphaned');
		$grandchild1_1_4_orphaned_published = $this->objFromFixture('Page',
			'grandchild1_1_4_orphaned_published'
		);
		$child2_1_published_orphaned = $this->objFromFixture('Page', 'child2_1_published_orphaned');
		
		$task = singleton('RemoveOrphanedPagesTask');
		$orphans = $task->getOrphanedPages();
		$orphanIDs = $orphans->column('ID');
		sort($orphanIDs);
		$compareIDs = array(
			$child1_3_orphaned->ID,
			$child1_4_orphaned_published->ID,
			$grandchild1_1_3_orphaned->ID,
			$grandchild1_1_4_orphaned_published->ID,
			$child2_1_published_orphaned->ID
		);
		sort($compareIDs);
		
		$this->assertEquals($orphanIDs, $compareIDs);
	}
	
}
?>