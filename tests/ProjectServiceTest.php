<?php

require_once __DIR__ . '/MemoryTestCase.php';

class ProjectServiceTest extends MemoryTestCase
{
    private $projectService;
    private $projectModelMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectModelMock = $this->createMock(ProjectModel::class);
        $this->projectService = new ProjectService($this->projectModelMock);
    }

    public function testCreateProjectAsProjectManager()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $projectData = [
            'name' => 'New Project',
            'description' => 'Project description',
            'id_user' => 1
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('add')
            ->with($projectData)
            ->willReturn(array_merge($projectData, ['id_project' => 1]));

        $result = $this->projectService->createProject($projectData);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id_project']);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateProjectFailsWithMissingName()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $projectData = [
            'description' => 'Description without name',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Project name is required');

        $this->projectService->createProject($projectData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateProjectFailsWithShortName()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $projectData = [
            'name' => 'AB',
            'description' => 'Description',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Project name must be at least 3 characters');

        $this->projectService->createProject($projectData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateProjectFailsWithLongName()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $projectData = [
            'name' => str_repeat('A', 256),
            'description' => 'Description',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Project name must not exceed 255 characters');

        $this->projectService->createProject($projectData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateProjectFailsAsDeveloper()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $projectData = [
            'name' => 'New Project',
            'description' => 'Description',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to create projects');

        $this->projectService->createProject($projectData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateProjectFailsWhenCreatingForOtherUser()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $projectData = [
            'name' => 'New Project',
            'description' => 'Description',
            'id_user' => 2
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You can only create projects for yourself');

        $this->projectService->createProject($projectData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateProjectSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingProject = (object) [
            'id_project' => 1,
            'name' => 'Old Name',
            'id_user' => 1
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingProject);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('modify')
            ->willReturn(array_merge((array)$existingProject, $updateData));

        $result = $this->projectService->updateProject(1, $updateData);

        $this->assertIsArray($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateProjectFailsWhenNotOwner()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingProject = (object) [
            'id_project' => 1,
            'id_user' => 2
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingProject);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to update this project');

        $this->projectService->updateProject(1, ['name' => 'New Name']);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateProjectSuccessAsAdmin()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingProject = (object) [
            'id_project' => 1,
            'id_user' => 2
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingProject);

        $this->projectModelMock
            ->expects($this->once())
            ->method('modify')
            ->willReturn(['id_project' => 1, 'name' => 'Updated']);

        $result = $this->projectService->updateProject(1, ['name' => 'Updated']);

        $this->assertIsArray($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testDeleteProjectSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingProject = (object) [
            'id_project' => 1,
            'id_user' => 1
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingProject);

        $this->projectModelMock
            ->expects($this->once())
            ->method('remove')
            ->with(1)
            ->willReturn(true);

        $result = $this->projectService->deleteProject(1);

        $this->assertTrue($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testAddUsersToProjectSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingProject = (object) [
            'id_project' => 1,
            'id_user' => 1
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingProject);

        $data = [
            'user_ids' => [2, 3],
            'project_id' => 1
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('addToProject')
            ->with($data)
            ->willReturn(['success' => true]);

        $result = $this->projectService->addUsersToProject($data);

        $this->assertEquals(['success' => true], $result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testAddUsersToProjectFailsWhenNotOwner()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingProject = (object) [
            'id_project' => 1,
            'id_user' => 2
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingProject);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to manage project users');

        $this->projectService->addUsersToProject([
            'user_ids' => [3],
            'project_id' => 1
        ]);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testRemoveUsersFromProjectSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingProject = (object) [
            'id_project' => 1,
            'id_user' => 1
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingProject);

        $data = [
            'user_ids' => [2],
            'project_id' => 1
        ];

        $this->projectModelMock
            ->expects($this->once())
            ->method('deleteFromProject')
            ->with($data)
            ->willReturn(['success' => true]);

        $result = $this->projectService->removeUsersFromProject($data);

        $this->assertEquals(['success' => true], $result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateProjectFailsWithMissingUserId()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $projectData = [
            'name' => 'New Project',
            'description' => 'Description'
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('User ID is required');

        $this->projectService->createProject($projectData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateProjectFailsWithNoFieldsToUpdate()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'projectManager'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('At least one field must be provided for update');

        $this->projectService->updateProject(1, []);

        unset($GLOBALS['jwt_user_data']);
    }
}
