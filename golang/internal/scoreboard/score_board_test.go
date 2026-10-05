package scoreboard

import (
	"errors"
	"fmt"
	"slices"
	"testing"
)

var example = []struct {
	homeTeam  string
	awayTeam  string
	homeScore int
	awayScore int
}{
	{"Mexico", "Canada", 0, 5},
	{"Spain", "Brazil", 10, 2},
	{"Germany", "France", 2, 2},
	{"Uruguay", "Italy", 6, 6},
	{"Argentina", "Australia", 3, 1},
}

var exampleSummary = []string{
	"Uruguay 6 - Italy 6",
	"Spain 10 - Brazil 2",
	"Mexico 0 - Canada 5",
	"Argentina 3 - Australia 1",
	"Germany 2 - France 2",
}

func newBoard() *ScoreBoard {
	return NewScoreBoard(NewInMemoryFootballMatchRepository())
}

// startGame starts a new match between the two teams on the board.
func startGame(t *testing.T, board *ScoreBoard, homeTeam, awayTeam string) *FootballMatch {
	t.Helper()

	match := newMatch(t, homeTeam, awayTeam)
	if err := board.StartGame(match); err != nil {
		t.Fatalf("StartGame() error = %v", err)
	}

	return match
}

func playExample(t *testing.T, board *ScoreBoard) []*FootballMatch {
	t.Helper()

	matches := []*FootballMatch{}
	for _, game := range example {
		match := startGame(t, board, game.homeTeam, game.awayTeam)
		if err := board.UpdateScore(match, game.homeScore, game.awayScore); err != nil {
			t.Fatalf("UpdateScore() error = %v", err)
		}

		matches = append(matches, match)
	}

	return matches
}

func describe(matches []*FootballMatch) []string {
	lines := []string{}
	for _, match := range matches {
		lines = append(lines, fmt.Sprintf("%s %d - %s %d", match.HomeTeam(), match.HomeScore(), match.AwayTeam(), match.AwayScore()))
	}

	return lines
}

func TestNewBoardIsEmpty(t *testing.T) {
	board := newBoard()

	assertMatches(t, board.Summary())
	assertMatches(t, board.FinishedGames())
}

func TestStartGameStartsMatchAndPutsItOnBoard(t *testing.T) {
	board := newBoard()
	match := newMatch(t, "Mexico", "Canada")

	if err := board.StartGame(match); err != nil {
		t.Fatalf("StartGame() error = %v", err)
	}

	if match.StartMatchTime().IsZero() {
		t.Error("StartMatchTime() is zero, want the start time")
	}

	assertMatches(t, board.Summary(), match)
}

func TestStartGameStoresMatchInGivenRepository(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()
	board := NewScoreBoard(repository)

	match := startGame(t, board, "Mexico", "Canada")

	if found, ok := repository.Find(match.MatchID()); !ok || found != match {
		t.Errorf("Find() = %v, %v, want the started match", found, ok)
	}
}

func TestTeamCannotPlayTwoMatchesAtOnce(t *testing.T) {
	tests := map[string][2]string{
		"home team is playing": {"Mexico", "Spain"},
		"away team is playing": {"Spain", "Canada"},
		"sides swapped":        {"Canada", "Mexico"},
		"different case":       {"MEXICO", "Spain"},
	}

	for name, teams := range tests {
		t.Run(name, func(t *testing.T) {
			board := newBoard()
			live := startGame(t, board, "Mexico", "Canada")
			rejected := newMatch(t, teams[0], teams[1])

			if err := board.StartGame(rejected); !errors.Is(err, ErrTeamAlreadyPlaying) {
				t.Errorf("StartGame() error = %v, want ErrTeamAlreadyPlaying", err)
			}

			if !rejected.StartMatchTime().IsZero() {
				t.Error("the rejected match was started")
			}

			assertMatches(t, board.Summary(), live)
		})
	}
}

func TestSameMatchCannotBeStartedTwice(t *testing.T) {
	board := newBoard()
	match := startGame(t, board, "Mexico", "Canada")

	if err := board.StartGame(match); !errors.Is(err, ErrTeamAlreadyPlaying) {
		t.Errorf("StartGame() error = %v, want ErrTeamAlreadyPlaying", err)
	}

	assertMatches(t, board.Summary(), match)
}

func TestFinishGameFinishesMatchAndRemovesItFromBoard(t *testing.T) {
	board := newBoard()
	match := startGame(t, board, "Mexico", "Canada")

	if err := board.FinishGame(match); err != nil {
		t.Fatalf("FinishGame() error = %v", err)
	}

	if match.FinishMatchTime().IsZero() {
		t.Error("FinishMatchTime() is zero, want the finish time")
	}

	assertMatches(t, board.Summary())
	assertMatches(t, board.FinishedGames(), match)
}

func TestTeamsCanPlayAgainAfterTheirMatchIsFinished(t *testing.T) {
	board := newBoard()
	first := startGame(t, board, "Mexico", "Canada")
	board.FinishGame(first)

	second := startGame(t, board, "Mexico", "Canada")

	assertMatches(t, board.Summary(), second)
}

func TestMatchThatIsNotOnBoardCannotBeFinished(t *testing.T) {
	board := newBoard()

	if err := board.FinishGame(newMatch(t, "Mexico", "Canada")); !errors.Is(err, ErrFootballMatchNotFound) {
		t.Errorf("FinishGame() error = %v, want ErrFootballMatchNotFound", err)
	}
}

func TestGameCannotBeFinishedTwice(t *testing.T) {
	board := newBoard()
	match := startGame(t, board, "Mexico", "Canada")
	board.FinishGame(match)

	if err := board.FinishGame(match); !errors.Is(err, ErrFootballMatchNotFound) {
		t.Errorf("FinishGame() error = %v, want ErrFootballMatchNotFound", err)
	}
}

func TestUpdateScoreChangesScoreOfMatchOnBoard(t *testing.T) {
	board := newBoard()
	match := startGame(t, board, "Mexico", "Canada")

	if err := board.UpdateScore(match, 0, 5); err != nil {
		t.Fatalf("UpdateScore() error = %v", err)
	}

	assertScore(t, match, 0, 5)
	assertMatches(t, board.Summary(), match)
}

func TestNegativeScoreIsRejectedByBoard(t *testing.T) {
	board := newBoard()
	match := startGame(t, board, "Mexico", "Canada")

	if err := board.UpdateScore(match, -1, 0); !errors.Is(err, ErrInvalidFootballMatch) {
		t.Errorf("UpdateScore() error = %v, want ErrInvalidFootballMatch", err)
	}

	assertScore(t, match, 0, 0)
}

func TestScoreOfMatchThatIsNotOnBoardCannotBeUpdated(t *testing.T) {
	board := newBoard()

	if err := board.UpdateScore(newMatch(t, "Mexico", "Canada"), 1, 0); !errors.Is(err, ErrFootballMatchNotFound) {
		t.Errorf("UpdateScore() error = %v, want ErrFootballMatchNotFound", err)
	}
}

func TestScoreOfFinishedMatchCannotBeUpdated(t *testing.T) {
	board := newBoard()
	match := startGame(t, board, "Mexico", "Canada")
	board.FinishGame(match)

	if err := board.UpdateScore(match, 1, 0); !errors.Is(err, ErrFootballMatchNotFound) {
		t.Errorf("UpdateScore() error = %v, want ErrFootballMatchNotFound", err)
	}
}

func TestSummaryIsOrderedByTotalScoreThenMostRecentlyStarted(t *testing.T) {
	board := newBoard()

	playExample(t, board)

	if got := describe(board.Summary()); !slices.Equal(got, exampleSummary) {
		t.Errorf("Summary() = %q, want %q", got, exampleSummary)
	}
}

func TestFinishedGamesUseSameOrderRegardlessOfFinishOrder(t *testing.T) {
	board := newBoard()
	matches := playExample(t, board)

	for _, index := range []int{2, 0, 4, 1, 3} {
		if err := board.FinishGame(matches[index]); err != nil {
			t.Fatalf("FinishGame() error = %v", err)
		}
	}

	assertMatches(t, board.Summary())

	if got := describe(board.FinishedGames()); !slices.Equal(got, exampleSummary) {
		t.Errorf("FinishedGames() = %q, want %q", got, exampleSummary)
	}
}

func TestChangingResultOfFinishedGamesDoesNotChangeBoard(t *testing.T) {
	board := newBoard()
	matches := playExample(t, board)
	for _, match := range matches {
		board.FinishGame(match)
	}

	slices.Reverse(board.FinishedGames())

	if got := describe(board.FinishedGames()); !slices.Equal(got, exampleSummary) {
		t.Errorf("FinishedGames() = %q, want %q", got, exampleSummary)
	}
}
