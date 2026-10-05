package scoreboard

import "fmt"

// ScoreBoardError is the type of every error the score board returns.
// Check for one of them with errors.Is, or for any of them with errors.As.
type ScoreBoardError string

func (e ScoreBoardError) Error() string {
	return string(e)
}

const (
	// ErrInvalidFootballMatch means the given teams, id or score are not allowed.
	ErrInvalidFootballMatch ScoreBoardError = "invalid football match"
	// ErrInvalidFootballMatchState means the action does not fit the state of
	// the match, for example a score update after the match was finished.
	ErrInvalidFootballMatchState ScoreBoardError = "invalid football match state"
	// ErrFootballMatchNotFound means the match is not on the board: it was
	// never started there, or it is already finished.
	ErrFootballMatchNotFound ScoreBoardError = "football match not found"
	// ErrTeamAlreadyPlaying means one of the teams is in a match on the board.
	ErrTeamAlreadyPlaying ScoreBoardError = "team is already playing"
)

// messageError is one of the errors above with a message that says what
// exactly went wrong. The messages are the same sentences as in the PHP
// version, so both versions report an error in the same words.
type messageError struct {
	kind    ScoreBoardError
	message string
}

func newError(kind ScoreBoardError, format string, args ...any) error {
	return &messageError{kind: kind, message: fmt.Sprintf(format, args...)}
}

func (e *messageError) Error() string {
	return e.message
}

// Unwrap lets errors.Is and errors.As find the kind of the error.
func (e *messageError) Unwrap() error {
	return e.kind
}
