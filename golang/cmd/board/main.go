// Command board plays example matches and shows the score board summary.
package main

import (
	"fmt"
	"io"
	"os"
	"time"

	"worldcupscoreboard/internal/scoreboard"
)

var matches = []struct {
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

func main() {
	if err := run(os.Stdout, 500*time.Millisecond); err != nil {
		fmt.Fprintln(os.Stderr, err)
		os.Exit(1)
	}
}

// run plays the example matches and writes what happens to out. It waits for
// delay between the start of a match and its score.
func run(out io.Writer, delay time.Duration) error {
	board := scoreboard.NewScoreBoard(scoreboard.NewInMemoryFootballMatchRepository())

	fmt.Fprintln(out, "World Cup finals begin:")
	fmt.Fprintln(out)

	for _, game := range matches {
		match, err := scoreboard.NewFootballMatch(scoreboard.NewUUID(), game.homeTeam, game.awayTeam)
		if err != nil {
			return err
		}

		if err := board.StartGame(match); err != nil {
			return err
		}
		fmt.Fprintf(out, "Started: %s - %s\n", match.HomeTeam(), match.AwayTeam())

		time.Sleep(delay)

		if err := board.UpdateScore(match, game.homeScore, game.awayScore); err != nil {
			return err
		}
		fmt.Fprintf(out, "Score:   %s\n", describe(match))
	}

	writeList(out, "Summary (%d matches in progress):", board.Summary())

	return nil
}

func writeList(out io.Writer, title string, matches []*scoreboard.FootballMatch) {
	fmt.Fprintln(out)
	fmt.Fprintf(out, title+"\n", len(matches))

	for position, match := range matches {
		fmt.Fprintf(out, "%d. %s\n", position+1, describe(match))
	}
}

func describe(match *scoreboard.FootballMatch) string {
	return fmt.Sprintf("%s %d - %s %d", match.HomeTeam(), match.HomeScore(), match.AwayTeam(), match.AwayScore())
}
