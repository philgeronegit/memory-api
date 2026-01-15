<?php

require_once __DIR__ . '/MemoryTestCase.php';

class NoteServiceTest extends MemoryTestCase
{
    private $noteService;
    private $noteModelMock;
    private $authServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->noteModelMock = $this->createMock(NoteModel::class);
        $this->noteService = new NoteService($this->noteModelMock);
    }

    public function testCreateNoteWithValidData()
    {
        $userData = (object) [
            'id_user' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $noteData = [
            'title' => 'Test Note',
            'content' => 'This is test content',
            'type' => 'note',
            'id_user' => 1,
            'is_public' => true,
            'id_project' => null,
            'id_programming_language' => null
        ];

        $expectedResult = array_merge($noteData, ['id_note' => 1]);

        $this->noteModelMock
            ->expects($this->once())
            ->method('add')
            ->with($noteData)
            ->willReturn($expectedResult);

        $result = $this->noteService->createNote($noteData);

        $this->assertEquals($expectedResult, $result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateNoteFailsWithMissingTitle()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $noteData = [
            'content' => 'This is test content',
            'type' => 'note',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Title is required');

        $this->noteService->createNote($noteData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateNoteFailsWithMissingContent()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $noteData = [
            'title' => 'Test Note',
            'type' => 'note',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Content is required');

        $this->noteService->createNote($noteData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateNoteFailsWithInvalidType()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $noteData = [
            'title' => 'Test Note',
            'content' => 'Content',
            'type' => 'invalid_type',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Type must be one of: note, code, snippet');

        $this->noteService->createNote($noteData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testCreateNoteFailsWithoutAuthentication()
    {
        unset($GLOBALS['jwt_user_data']);

        $noteData = [
            'title' => 'Test Note',
            'content' => 'Content',
            'type' => 'note',
            'id_user' => 1
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Authentication required');

        $this->noteService->createNote($noteData);
    }

    public function testCreateNoteFailsWhenUserTriesToCreateForOtherUser()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $noteData = [
            'title' => 'Test Note',
            'content' => 'Content',
            'type' => 'note',
            'id_user' => 2
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You can only create notes for yourself');

        $this->noteService->createNote($noteData);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateNoteSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingNote = (object) [
            'id_note' => 1,
            'title' => 'Old Title',
            'content' => 'Old Content',
            'id_user' => 1
        ];

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated Content'
        ];

        $this->noteModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingNote);

        $this->noteModelMock
            ->expects($this->once())
            ->method('modify')
            ->willReturn(array_merge((array)$existingNote, $updateData, ['id' => 1]));

        $result = $this->noteService->updateNote(1, $updateData);

        $this->assertIsArray($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateNoteFailsWhenNoteNotFound()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $this->noteModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(999)
            ->willReturn((object)[]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Note not found');

        $this->noteService->updateNote(999, ['title' => 'New Title']);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateNoteFailsWhenUserDoesNotOwnNote()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingNote = (object) [
            'id_note' => 1,
            'id_user' => 2
        ];

        $this->noteModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingNote);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to update this note');

        $this->noteService->updateNote(1, ['title' => 'New Title']);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testUpdateNoteSuccessWhenUserIsAdmin()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'admin'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingNote = (object) [
            'id_note' => 1,
            'id_user' => 2
        ];

        $this->noteModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingNote);

        $this->noteModelMock
            ->expects($this->once())
            ->method('modify')
            ->willReturn(['id' => 1, 'title' => 'Updated']);

        $result = $this->noteService->updateNote(1, ['title' => 'Updated']);

        $this->assertIsArray($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testDeleteNoteSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingNote = (object) [
            'id_note' => 1,
            'id_user' => 1
        ];

        $this->noteModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingNote);

        $this->noteModelMock
            ->expects($this->once())
            ->method('remove')
            ->with(1)
            ->willReturn(true);

        $result = $this->noteService->deleteNote(1);

        $this->assertTrue($result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testShareNoteSuccess()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingNote = (object) [
            'id_note' => 1,
            'id_user' => 1
        ];

        $shareData = [
            'id_item' => 1,
            'id_user' => 2
        ];

        $this->noteModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingNote);

        $this->noteModelMock
            ->expects($this->once())
            ->method('shareNoteWithUser')
            ->with($shareData)
            ->willReturn(['success' => true]);

        $result = $this->noteService->shareNote($shareData);

        $this->assertEquals(['success' => true], $result);

        unset($GLOBALS['jwt_user_data']);
    }

    public function testShareNoteFailsWhenNotOwner()
    {
        $userData = (object) [
            'id_user' => 1,
            'role' => 'developer'
        ];
        $GLOBALS['jwt_user_data'] = $userData;

        $existingNote = (object) [
            'id_note' => 1,
            'id_user' => 2
        ];

        $this->noteModelMock
            ->expects($this->once())
            ->method('getOne')
            ->with(1)
            ->willReturn($existingNote);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have permission to share this note');

        $this->noteService->shareNote(['id_item' => 1, 'id_user' => 3]);

        unset($GLOBALS['jwt_user_data']);
    }
}
