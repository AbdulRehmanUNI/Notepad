<?php

class NotepadCLI {
    private $file = 'notes.txt';
    private $history = [];
    private $future = [];

    public function run() {
        echo "Welcome to NotepadCLI. Type 'help' to see available commands.\n";
        $this->interactiveMode();
    }

    private function interactiveMode() {
        while (true) {
            echo ">> ";
            $input = trim(fgets(STDIN));
            
            $startTime = microtime(true);
            $this->processInput($input);
            $endTime = microtime(true);

            $executionTime = round($endTime - $startTime, 4);
            $memoryUsage = memory_get_usage() / 1024; // Convert to KB

            echo "Time taken: {$executionTime} seconds\n";
            echo "Memory used: {$memoryUsage} KB\n";

            // Perform garbage collection after each command
            gc_collect_cycles();
        }
    }

    private function processInput($input) {
        $parts = explode(' ', $input, 2);
        $command = $parts[0] ?? null;
        $note = $parts[1] ?? '';

        switch ($command) {
            case 'add':
                $this->add($note);
                break;
            case 'show':
                $this->show();
                break;
            case 'delete':
                $this->delete($note);
                break;
            case 'search':
                $this->search($note);
                break;
            case 'undo':
                $this->undo();
                break;
            case 'redo':
                $this->redo();
                break;
            case 'exit':
                exit("Exiting NotepadCLI.\n");
            case 'help':
                $this->help();
                break;
            case 'print':
                var_dump($this->history);
                var_dump($this->future);
                break;
            case 'test':
                $this->test();
                break;
            default:
                echo "Unknown command. Type 'help' to see available commands.\n";
        }
    }

    private function test() {
        for ($i = 1; $i <= 10; $i++) {
            $note = "Line $i added at " . date('Y-m-d H:i:s');
            $this->add($note);
        }
    }

    private function add($note) {
        $notes = file($this->file, FILE_IGNORE_NEW_LINES);
        $notes[] = $note;
        file_put_contents($this->file, implode(PHP_EOL, $notes) . PHP_EOL);
        $this->history[] = ['add', $note, count($notes) - 1];
        echo "Note added.\n";
    }

    private function show() {
        $notes = file($this->file, FILE_IGNORE_NEW_LINES);
        foreach ($notes as $key => $note) {
            echo str_pad($key + 1, 4, ' ', STR_PAD_LEFT) . " | " . $note . PHP_EOL;
        }
    }

    private function delete($note) {
        $notes = file($this->file, FILE_IGNORE_NEW_LINES);
        $found = false;
        $newNotes = [];
        foreach ($notes as $key => $value) {
            if ($value === $note && !$found) {
                $this->history[] = ['delete', $note, $key];
                $found = true;
                continue;
            }
            $newNotes[] = $value;
        }
        if ($found) {
            file_put_contents($this->file, implode(PHP_EOL, $newNotes) . PHP_EOL);
            echo "Note deleted.\n";
        } else {
            echo "Note not found.\n";
        }
    }

    private function search($note) {
        $notes = file($this->file);
        $found = false;
        foreach ($notes as $line) {
            if (strpos($line, $note) !== false) {
                echo "Note found: " . $line;
                $found = true;
            }
        }
        if (!$found) {
            echo "Note not found.\n";
        }
    }

    private function undo() {
        if (!empty($this->history)) {
            $lastAction = array_pop($this->history);
            $this->future[] = $lastAction;

            $notes = file($this->file, FILE_IGNORE_NEW_LINES);
            if ($lastAction[0] === 'add') {
                unset($notes[$lastAction[2]]);
            } else if ($lastAction[0] === 'delete') {
                array_splice($notes, $lastAction[2], 0, $lastAction[1]);
            }
            file_put_contents($this->file, implode(PHP_EOL, $notes) . PHP_EOL);
            echo "Undo successful.\n";
        } else {
            echo "No actions to undo.\n";
        }
    }

    private function redo() {
        if (!empty($this->future)) {
            $lastAction = array_pop($this->future);
            $this->history[] = $lastAction;

            $notes = file($this->file, FILE_IGNORE_NEW_LINES);
            if ($lastAction[0] === 'add') {
                array_splice($notes, $lastAction[2], 0, $lastAction[1]);
            } else if ($lastAction[0] === 'delete') {
                unset($notes[$lastAction[2]]);
            }
            file_put_contents($this->file, implode(PHP_EOL, $notes) . PHP_EOL);
            echo "Redo successful.\n";
        } else {
            echo "No actions to redo.\n";
        }
    }

    private function help() {
        echo "Available commands:\n";
        echo "  add [note] - Add a new note\n";
        echo "  show - Show all notes\n";
        echo "  delete [note] - Delete a note\n";
        echo "  search [note] - Search for a note\n";
        echo "  undo - Undo the last action\n";
        echo "  redo - Redo the last undone action\n";
        echo "  exit - Exit the application\n";
    }
}

$notepad = new NotepadCLI();
$notepad->run();
