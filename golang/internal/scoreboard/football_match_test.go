package scoreboard

import (
	"errors"
	"testing"
)

func newMatch(t *testing.T, homeTeam, awayTeam string) *FootballMatch {
	t.Helper()

	match, err := NewFootballMatch(NewUUID(), homeTeam, awayTeam)
	if err != nil {
		t.Fatalf("NewFootballMatch() error = %v", err)
	}

	return match
}

func startedMatch(t *testing.T) *FootballMatch {
	t.Helper()

	match := newMatch(t, "Mexico", "Canada")
	if err := match.StartMatch(); err != nil {
		t.Fatalf("StartMatch() error = %v", err)
	}

	return match
}

func assertScore(t *testing.T, match *FootballMatch, homeScore, awayScore int) {
	t.Helper()

	if match.HomeScore() != homeScore || match.AwayScore() != awayScore {
		t.Errorf("score = %d - %d, want %d - %d", match.HomeScore(), match.AwayScore(), homeScore, awayScore)
	}
}

func TestNewMatchIsNotStartedAndHasNoGoals(t *testing.T) {
	match := newMatch(t, " Mexico ", "Canada")

	if match.HomeTeam() != "Mexico" || match.AwayTeam() != "Canada" {
		t.Errorf("teams = %q, %q, want \"Mexico\", \"Canada\"", match.HomeTeam(), match.AwayTeam())
	}

	assertScore(t, match, 0, 0)

	if !match.StartMatchTime().IsZero() {
		t.Errorf("StartMatchTime() = %v, want zero", match.StartMatchTime())
	}

	if !match.FinishMatchTime().IsZero() {
		t.Errorf("FinishMatchTime() = %v, want zero", match.FinishMatchTime())
	}
}

func TestMatchKeepsGivenID(t *testing.T) {
	matchID, _ := UUIDFromString(uuidValue)

	match, err := NewFootballMatch(matchID, "Mexico", "Canada")
	if err != nil {
		t.Fatalf("NewFootballMatch() error = %v", err)
	}

	if match.MatchID() != matchID {
		t.Errorf("MatchID() = %v, want %v", match.MatchID(), matchID)
	}
}

func TestEmptyMatchIDIsRejected(t *testing.T) {
	if _, err := NewFootballMatch(UUID{}, "Mexico", "Canada"); !errors.Is(err, ErrInvalidFootballMatch) {
		t.Errorf("NewFootballMatch() error = %v, want ErrInvalidFootballMatch", err)
	}
}

func TestInvalidTeamsAreRejected(t *testing.T) {
	tests := map[string][2]string{
		"empty home team":           {"", "Canada"},
		"blank away team":           {"Mexico", "   "},
		"same team":                 {"Spain", "Spain"},
		"same team, different case": {"Spain", " SPAIN "},
	}

	for name, teams := range tests {
		t.Run(name, func(t *testing.T) {
			if _, err := NewFootballMatch(NewUUID(), teams[0], teams[1]); !errors.Is(err, ErrInvalidFootballMatch) {
				t.Errorf("NewFootballMatch() error = %v, want ErrInvalidFootballMatch", err)
			}
		})
	}
}

func TestStartMatchSetsStartTime(t *testing.T) {
	match := newMatch(t, "Mexico", "Canada")

	if err := match.StartMatch(); err != nil {
		t.Fatalf("StartMatch() error = %v", err)
	}

	if match.StartMatchTime().IsZero() {
		t.Error("StartMatchTime() is zero, want the start time")
	}

	if !match.FinishMatchTime().IsZero() {
		t.Errorf("FinishMatchTime() = %v, want zero", match.FinishMatchTime())
	}
}

func TestMatchCannotBeStartedTwice(t *testing.T) {
	match := startedMatch(t)

	if err := match.StartMatch(); !errors.Is(err, ErrInvalidFootballMatchState) {
		t.Errorf("StartMatch() error = %v, want ErrInvalidFootballMatchState", err)
	}
}

func TestUpdateScoreReplacesScore(t *testing.T) {
	match := startedMatch(t)

	if err := match.UpdateScore(0, 5); err != nil {
		t.Fatalf("UpdateScore() error = %v", err)
	}

	assertScore(t, match, 0, 5)
}

func TestScoreCanBeLowered(t *testing.T) {
	match := startedMatch(t)
	match.UpdateScore(2, 1)

	if err := match.UpdateScore(1, 1); err != nil {
		t.Fatalf("UpdateScore() error = %v", err)
	}

	assertScore(t, match, 1, 1)
}

func TestNegativeScoreIsRejected(t *testing.T) {
	match := startedMatch(t)

	if err := match.UpdateScore(1, -1); !errors.Is(err, ErrInvalidFootballMatch) {
		t.Errorf("UpdateScore() error = %v, want ErrInvalidFootballMatch", err)
	}

	assertScore(t, match, 0, 0)
}

func TestScoreCannotBeUpdatedBeforeStart(t *testing.T) {
	match := newMatch(t, "Mexico", "Canada")

	if err := match.UpdateScore(1, 0); !errors.Is(err, ErrInvalidFootballMatchState) {
		t.Errorf("UpdateScore() error = %v, want ErrInvalidFootballMatchState", err)
	}
}

func TestScoreCannotBeUpdatedAfterFinish(t *testing.T) {
	match := startedMatch(t)
	match.FinishMatch()

	if err := match.UpdateScore(1, 0); !errors.Is(err, ErrInvalidFootballMatchState) {
		t.Errorf("UpdateScore() error = %v, want ErrInvalidFootballMatchState", err)
	}
}

func TestFinishMatchSetsFinishTimeAndKeepsScore(t *testing.T) {
	match := startedMatch(t)
	match.UpdateScore(3, 1)

	if err := match.FinishMatch(); err != nil {
		t.Fatalf("FinishMatch() error = %v", err)
	}

	if match.FinishMatchTime().IsZero() {
		t.Error("FinishMatchTime() is zero, want the finish time")
	}

	assertScore(t, match, 3, 1)
}

func TestMatchCannotBeFinishedBeforeStart(t *testing.T) {
	match := newMatch(t, "Mexico", "Canada")

	if err := match.FinishMatch(); !errors.Is(err, ErrInvalidFootballMatchState) {
		t.Errorf("FinishMatch() error = %v, want ErrInvalidFootballMatchState", err)
	}
}

func TestMatchCannotBeFinishedTwice(t *testing.T) {
	match := startedMatch(t)
	match.FinishMatch()

	if err := match.FinishMatch(); !errors.Is(err, ErrInvalidFootballMatchState) {
		t.Errorf("FinishMatch() error = %v, want ErrInvalidFootballMatchState", err)
	}
}

func TestEveryMatchErrorIsAScoreBoardError(t *testing.T) {
	_, err := NewFootballMatch(NewUUID(), "", "Canada")

	var scoreBoardError ScoreBoardError
	if !errors.As(err, &scoreBoardError) {
		t.Errorf("error %v is not a ScoreBoardError", err)
	}
}
