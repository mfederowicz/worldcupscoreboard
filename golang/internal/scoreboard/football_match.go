package scoreboard

import (
	"strings"
	"time"
)

// FootballMatch is one match: two teams, the score and when it was started
// and finished. The fields are private, so they change only through the
// methods below.
type FootballMatch struct {
	matchID         UUID
	homeTeam        string
	awayTeam        string
	homeScore       int
	awayScore       int
	startMatchTime  time.Time
	finishMatchTime time.Time
}

// NewFootballMatch returns a match that is not started yet, at 0 - 0.
// Team names are trimmed; they must not be empty and must be two different
// teams (letter case does not matter).
func NewFootballMatch(matchID UUID, homeTeam, awayTeam string) (*FootballMatch, error) {
	homeTeam = strings.TrimSpace(homeTeam)
	awayTeam = strings.TrimSpace(awayTeam)

	if matchID == (UUID{}) {
		return nil, newError(ErrInvalidFootballMatch, "Match id must not be empty.")
	}

	if homeTeam == "" || awayTeam == "" {
		return nil, newError(ErrInvalidFootballMatch, "Team name must not be empty.")
	}

	if strings.EqualFold(homeTeam, awayTeam) {
		return nil, newError(ErrInvalidFootballMatch, "A team cannot play against itself.")
	}

	return &FootballMatch{matchID: matchID, homeTeam: homeTeam, awayTeam: awayTeam}, nil
}

func (m *FootballMatch) MatchID() UUID    { return m.matchID }
func (m *FootballMatch) HomeTeam() string { return m.homeTeam }
func (m *FootballMatch) AwayTeam() string { return m.awayTeam }
func (m *FootballMatch) HomeScore() int   { return m.homeScore }
func (m *FootballMatch) AwayScore() int   { return m.awayScore }

// StartMatchTime is the zero time until the match is started.
func (m *FootballMatch) StartMatchTime() time.Time { return m.startMatchTime }

// FinishMatchTime is the zero time until the match is finished.
func (m *FootballMatch) FinishMatchTime() time.Time { return m.finishMatchTime }

// StartMatch marks the match as started now. A match can be started once.
func (m *FootballMatch) StartMatch() error {
	if !m.startMatchTime.IsZero() {
		return newError(ErrInvalidFootballMatchState, "Match has already been started.")
	}

	m.startMatchTime = time.Now()

	return nil
}

// FinishMatch marks a match in progress as finished now.
func (m *FootballMatch) FinishMatch() error {
	if err := m.checkInProgress(); err != nil {
		return err
	}

	m.finishMatchTime = time.Now()

	return nil
}

// UpdateScore replaces the score of a match in progress. The new score may be
// lower than the current one, for example after a disallowed goal.
func (m *FootballMatch) UpdateScore(homeScore, awayScore int) error {
	if err := m.checkInProgress(); err != nil {
		return err
	}

	if homeScore < 0 || awayScore < 0 {
		return newError(ErrInvalidFootballMatch, "Score must not be negative.")
	}

	m.homeScore = homeScore
	m.awayScore = awayScore

	return nil
}

func (m *FootballMatch) checkInProgress() error {
	if m.startMatchTime.IsZero() || !m.finishMatchTime.IsZero() {
		return newError(ErrInvalidFootballMatchState, "Match is not in progress.")
	}

	return nil
}
