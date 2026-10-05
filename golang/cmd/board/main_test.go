package main

import (
	"strings"
	"testing"
)

func runDemo(t *testing.T) string {
	t.Helper()

	var out strings.Builder
	if err := run(&out, 0); err != nil {
		t.Fatalf("run() error = %v", err)
	}

	return out.String()
}

func TestShowsEachMatchBeingStartedAndScored(t *testing.T) {
	output := runDemo(t)

	start := "World Cup finals begin:\n\nStarted: Mexico - Canada\nScore:   Mexico 0 - Canada 5\n"
	if !strings.HasPrefix(output, start) {
		t.Errorf("output does not start with %q:\n%s", start, output)
	}

	last := "Started: Argentina - Australia\nScore:   Argentina 3 - Australia 1\n"
	if !strings.Contains(output, last) {
		t.Errorf("output does not contain %q:\n%s", last, output)
	}
}

func TestEndsWithSummaryOfAllMatchesOrderedByTotalScore(t *testing.T) {
	want := `
Summary (5 matches in progress):
1. Uruguay 6 - Italy 6
2. Spain 10 - Brazil 2
3. Mexico 0 - Canada 5
4. Argentina 3 - Australia 1
5. Germany 2 - France 2
`

	if output := runDemo(t); !strings.HasSuffix(output, want) {
		t.Errorf("output does not end with %q:\n%s", want, output)
	}
}
